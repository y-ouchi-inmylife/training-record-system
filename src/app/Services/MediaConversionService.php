<?php

namespace App\Services;

use App\Models\MediaRecord;
use Illuminate\Http\File;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * メディア変換サービス
 *
 * 原本（オブジェクトストレージ）→ ローカル一時ファイル → 変換 → 表示用ファイル（オブジェクトストレージ）
 * のデータフローを担う。原本は常に残し、変換後の表示用ファイルを別キーで書き出す。
 *
 * 写真変換（heic/heif → jpeg）と動画変換（mov → mp4）を提供する。
 * 共通の骨格（get→tmp→Process→putFileAs→finally）と UTF-8 対策（toUtf8, 固定文言例外）を
 * 揃え、変換コマンドだけ写真/動画で分岐させる構成。
 */
class MediaConversionService
{
    // 表示用 jpeg の品質（画面表示用の標準品質。原本は残すので印刷品質は原本で確保）
    private const JPEG_QUALITY = 85;

    // 一時ファイル置き場（storage/app 配下は git 管理外）
    private const TMP_DIR = 'tmp/conversion';

    // 動画変換（ffmpeg）の実行タイムアウト（秒）
    // 写真は短時間で済むため 300 秒で十分だが、動画は長尺になり得るので余裕を見て 600 秒。
    private const VIDEO_PROCESS_TIMEOUT = 600;

    /**
     * 写真（heic/heif）を jpeg に変換し、変換後ファイルのストレージキーを返す
     *
     * @param string $originalPath 原本のストレージキー（例: media/202606/{uuid}.heic）
     * @return string 変換後（表示用）のストレージキー（例: media/202606/{uuid}.jpg）
     * @throws \RuntimeException 変換失敗時
     */
    public function convertPhotoToJpeg(string $originalPath): string
    {
        $tmpDir = storage_path('app/' . self::TMP_DIR);
        FileFacade::ensureDirectoryExists($tmpDir);

        // 衝突回避のため一時ファイル名に uuid を使う（元の uuid は表示用キーで使うので別系統）
        $tmpId = (string) Str::uuid();
        $originalExt = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'bin';
        $tmpIn = $tmpDir . DIRECTORY_SEPARATOR . $tmpId . '.' . $originalExt;
        $tmpOut = $tmpDir . DIRECTORY_SEPARATOR . $tmpId . '.jpg';

        try {
            // 1. 原本をオブジェクトストレージから取得し、一時ファイルに書き出す（ストリームコピー）
            $this->downloadOriginalToTempFile($originalPath, $tmpIn);

            // 2. magick で heic→jpeg 変換
            //    -auto-orient: heic に多い EXIF orientation を表示用に焼き込む
            //    -quality: 画面表示用の標準品質
            //    magick のパスは config 経由（Windows 開発ではフルパス必須、Linux 本番は 'magick' で PATH 解決）
            $magickPath = (string) config('media.magick_path', 'magick');
            $result = Process::timeout(300)->run([
                $magickPath,
                $tmpIn,
                '-auto-orient',
                '-quality', (string) self::JPEG_QUALITY,
                $tmpOut,
            ]);

            // Process 失敗時、$result->throw() の例外メッセージには magick の stderr が含まれる。
            // Windows では cmd.exe が CP932（Shift-JIS）でエラーを返すため、そのまま例外に乗せると
            // 上位の json_encode で Malformed UTF-8 になる。stderr は UTF-8 化してログにだけ記録し、
            // 例外は UTF-8 確定の固定文言に包み直して投げる（呼び出し側でそのまま JSON 化可能）。
            if ($result->failed()) {
                Log::error('MediaConversionService: magick 実行失敗', [
                    'exit_code' => $result->exitCode(),
                    'stderr' => $this->toUtf8($result->errorOutput()),
                    'original_path' => $originalPath,
                ]);
                throw new \RuntimeException(
                    "表示用変換に失敗しました（exit code {$result->exitCode()}）。詳細はサーバログを確認してください。"
                );
            }

            if (!FileFacade::exists($tmpOut)) {
                throw new \RuntimeException('変換後ファイルが生成されませんでした。');
            }

            // 3. 変換後ファイルをオブジェクトストレージに書き戻す
            //    変換後キー: 原本と同じディレクトリ・同じファイル名（uuid）で拡張子を .jpg に
            $displayPath = $this->buildDisplayPath($originalPath, 'jpg');
            $dir = pathinfo($displayPath, PATHINFO_DIRNAME);
            $basename = pathinfo($displayPath, PATHINFO_BASENAME);

            Storage::disk(MediaRecord::STORAGE_DISK)->putFileAs($dir, new File($tmpOut), $basename);

            return $displayPath;
        } finally {
            // 4. 一時ファイルは必ず削除（成功時も失敗時も）
            FileFacade::delete([$tmpIn, $tmpOut]);
        }
    }

    /**
     * 動画（mov）を mp4 に変換し、変換後ファイルのストレージキーを返す
     *
     * ブラウザ再生互換性を担保するため、コンテナ詰替えではなく常に再エンコードする。
     * H.264 (libx264) + AAC + yuv420p で iOS/Android/Safari/Chrome すべてで再生可能とし、
     * faststart で moov atom を先頭に置きストリーミング再生（バイト範囲リクエスト）を可能にする。
     *
     * @param string $originalPath 原本のストレージキー（例: media/202606/{uuid}.mov）
     * @return string 変換後（表示用）のストレージキー（例: media/202606/{uuid}.mp4）
     * @throws \RuntimeException 変換失敗時
     */
    public function convertVideoToMp4(string $originalPath): string
    {
        $tmpDir = storage_path('app/' . self::TMP_DIR);
        FileFacade::ensureDirectoryExists($tmpDir);

        $tmpId = (string) Str::uuid();
        $originalExt = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'bin';
        $tmpIn = $tmpDir . DIRECTORY_SEPARATOR . $tmpId . '.' . $originalExt;
        $tmpOut = $tmpDir . DIRECTORY_SEPARATOR . $tmpId . '.mp4';

        try {
            // 1. 原本をオブジェクトストレージから取得し、一時ファイルに書き出す（ストリームコピー）
            $this->downloadOriginalToTempFile($originalPath, $tmpIn);

            // 2. ffmpeg で mov→mp4 変換（常に再エンコード）
            //    -y: 出力ファイル上書き許可
            //    -c:v libx264 -preset medium -crf 23: 標準的な品質/速度バランス
            //    -pix_fmt yuv420p: ブラウザ再生互換性のため必須（High 4:4:4 等の罠回避）
            //    -c:a aac -b:a 128k: 音声は AAC 128kbps
            //    -movflags +faststart: moov atom を先頭に置きストリーミング再生可能に
            //    ffmpeg のパスは config 経由（Windows 開発ではフルパス必須、Linux 本番は 'ffmpeg' で PATH 解決）
            $ffmpegPath = (string) config('media.ffmpeg_path', 'ffmpeg');
            $result = Process::timeout(self::VIDEO_PROCESS_TIMEOUT)->run([
                $ffmpegPath,
                '-y',
                '-i', $tmpIn,
                '-c:v', 'libx264',
                '-preset', 'medium',
                '-crf', '23',
                '-pix_fmt', 'yuv420p',
                '-c:a', 'aac',
                '-b:a', '128k',
                '-movflags', '+faststart',
                $tmpOut,
            ]);

            // 写真変換と同じく UTF-8 対策: stderr はログのみ、例外は固定文言。
            // ffmpeg は進捗を stderr に大量出力するが、failed 時のみ拾うので量は問題なし。
            if ($result->failed()) {
                Log::error('MediaConversionService: ffmpeg 実行失敗', [
                    'exit_code' => $result->exitCode(),
                    'stderr' => $this->toUtf8($result->errorOutput()),
                    'original_path' => $originalPath,
                ]);
                throw new \RuntimeException(
                    "表示用変換に失敗しました（exit code {$result->exitCode()}）。詳細はサーバログを確認してください。"
                );
            }

            if (!FileFacade::exists($tmpOut)) {
                throw new \RuntimeException('変換後ファイルが生成されませんでした。');
            }

            // 3. 変換後ファイルをオブジェクトストレージに書き戻す
            //    変換後キー: 原本と同じディレクトリ・同じファイル名（uuid）で拡張子を .mp4 に
            $displayPath = $this->buildDisplayPath($originalPath, 'mp4');
            $dir = pathinfo($displayPath, PATHINFO_DIRNAME);
            $basename = pathinfo($displayPath, PATHINFO_BASENAME);

            Storage::disk(MediaRecord::STORAGE_DISK)->putFileAs($dir, new File($tmpOut), $basename);

            return $displayPath;
        } finally {
            FileFacade::delete([$tmpIn, $tmpOut]);
        }
    }

    /**
     * 原本パスから表示用ファイルのキーを組み立てる（拡張子は呼び出し側が指定）
     *
     * 例: media/202606/{uuid}.heic, 'jpg' → media/202606/{uuid}.jpg
     * 例: media/202606/{uuid}.mov,  'mp4' → media/202606/{uuid}.mp4
     */
    private function buildDisplayPath(string $originalPath, string $newExtension): string
    {
        $dir = pathinfo($originalPath, PATHINFO_DIRNAME);
        $name = pathinfo($originalPath, PATHINFO_FILENAME);
        // dirname が '.' になるケースは想定外（storage_key は必ず media/YYYYMM/... 形式）
        return ($dir === '.' || $dir === '') ? $name . '.' . $newExtension : $dir . '/' . $name . '.' . $newExtension;
    }

    /**
     * オブジェクトストレージの原本ファイルを、メモリにフルロードせず
     * ストリームで一時ファイルにダウンロードする。
     *
     * Storage::get() は対象ファイル全体を PHP のメモリに読み込むため、
     * 大容量メディア（数百MB〜1GB）で memory_limit を簡単に超える。
     * readStream + stream_copy_to_stream に変更し、固定バッファでコピーすることで
     * ファイルサイズに依存せず PHP メモリ使用量を一定に保つ。
     *
     * MediaThumbnailService に同じ実装がある。3つ目の Service が出てきたら trait 化を検討
     * （toUtf8 と同じ方針）。
     */
    private function downloadOriginalToTempFile(string $originalPath, string $tmpIn): void
    {
        $readStream = Storage::disk(MediaRecord::STORAGE_DISK)->readStream($originalPath);
        if ($readStream === null || $readStream === false) {
            throw new \RuntimeException("原本ファイルが取得できません: {$originalPath}");
        }
        $writeStream = fopen($tmpIn, 'wb');
        if ($writeStream === false) {
            fclose($readStream);
            throw new \RuntimeException("一時ファイルを開けません: {$tmpIn}");
        }
        try {
            if (stream_copy_to_stream($readStream, $writeStream) === false) {
                throw new \RuntimeException("原本ダウンロード中にエラーが発生しました: {$originalPath}");
            }
        } finally {
            if (is_resource($readStream)) {
                fclose($readStream);
            }
            if (is_resource($writeStream)) {
                fclose($writeStream);
            }
        }
    }

    /**
     * 外部プロセスの stdout/stderr を UTF-8 化する
     *
     * Windows の cmd.exe は CP932（Shift-JIS）で日本語エラーを返すため、そのまま
     * Log や JSON に渡すと Malformed UTF-8 で連鎖事故になる。Windows系の代表的な
     * エンコーディングからの推定変換を行い、UTF-8 として安全な文字列を返す。
     */
    private function toUtf8(string $s): string
    {
        if ($s === '' || mb_check_encoding($s, 'UTF-8')) {
            return $s;
        }
        $converted = mb_convert_encoding($s, 'UTF-8', 'UTF-8,SJIS-win,CP932,SJIS,EUC-JP');
        return $converted === false ? '' : $converted;
    }
}
