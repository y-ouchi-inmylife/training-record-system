<?php

namespace App\Services;

use Illuminate\Http\File;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * メディアサムネイル生成サービス
 *
 * 原本（オブジェクトストレージ）→ ローカル一時ファイル → サムネイル生成 → サムネイル（オブジェクトストレージ）
 * のデータフローを担う。原本から直接生成し（表示用変換の完了を待たない）、200x200・余白付きの
 * jpeg として thumbnails/YYYYMM/{uuid}.jpg に保存する。
 *
 * 写真サムネイル（jpeg/png/heic 原本 → jpeg）と動画サムネイル（mov/mp4 原本 → jpeg）を提供する。
 * 動画は「FFmpeg でフレーム抽出（無劣化 png）→ ImageMagick でリサイズ・余白」の2段構成にすることで、
 * 写真と動画でリサイズ・余白・JPEG 品質を完全に同じ magick コマンドに通し、一覧で見た目を揃える。
 */
class MediaThumbnailService
{
    // サムネイル寸法（一覧カードの .ratio-1x1 と一致）
    private const THUMBNAIL_SIZE = 200;

    // サムネイル jpeg の品質（変換と揃える）
    private const JPEG_QUALITY = 85;

    // 余白色（写真の外側を白で塗りつぶす。一覧カードに自然に馴染む色）
    private const BACKGROUND_COLOR = '#ffffff';

    // オブジェクトストレージのディスク名（MediaRecordController と揃える）
    private const STORAGE_DISK = 'sakura';

    // 一時ファイル置き場（storage/app 配下は git 管理外）
    private const TMP_DIR = 'tmp/thumbnail';

    // 動画サムネイル用：フレーム抽出時刻（秒）
    // 0秒は暗転や前置きで真っ黒になりがちなので 1 秒目を取る。
    // 1秒未満の動画は実用上ほぼ無く、仮にあれば ffmpeg が失敗→ error ステータスで止まる。
    private const VIDEO_THUMBNAIL_SEEK_SECONDS = 1;

    /**
     * 写真原本（jpeg/png/heic）から 200x200・余白付き jpeg サムネイルを生成し、
     * 保存後のストレージキーを返す
     *
     * @param string $originalPath 原本のストレージキー（例: media/202606/{uuid}.heic）
     * @return string サムネイルのストレージキー（例: thumbnails/202606/{uuid}.jpg）
     * @throws \RuntimeException 生成失敗時
     */
    public function generatePhotoThumbnail(string $originalPath): string
    {
        $tmpDir = storage_path('app/' . self::TMP_DIR);
        FileFacade::ensureDirectoryExists($tmpDir);

        $tmpId = (string) Str::uuid();
        $originalExt = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'bin';
        $tmpIn = $tmpDir . DIRECTORY_SEPARATOR . $tmpId . '.' . $originalExt;
        $tmpOut = $tmpDir . DIRECTORY_SEPARATOR . $tmpId . '.jpg';

        try {
            // 1. 原本をオブジェクトストレージから取得し、一時ファイルに書き出す
            $bytes = Storage::disk(self::STORAGE_DISK)->get($originalPath);
            if ($bytes === null) {
                throw new \RuntimeException("原本ファイルが取得できません: {$originalPath}");
            }
            FileFacade::put($tmpIn, $bytes);

            // 2. magick で 200x200 サムネイル生成（アスペクト比保持で枠に収め、余白を白で埋める）
            //    -auto-orient: heic に多い EXIF orientation を焼き込む（縦撮影が横にならないように）
            //    -resize 200x200: アスペクト比保持で 200x200 の枠に収まるよう縮小
            //    -background/-gravity/-extent: 200x200 キャンバスに中央配置、余白は白で埋める
            //    -quality: 一覧表示用の標準品質
            //    magick のパスは config 経由（写真変換と同じ）
            $size = self::THUMBNAIL_SIZE . 'x' . self::THUMBNAIL_SIZE;
            $magickPath = (string) config('media.magick_path', 'magick');
            $result = Process::timeout(300)->run([
                $magickPath,
                $tmpIn,
                '-auto-orient',
                '-resize', $size,
                '-background', self::BACKGROUND_COLOR,
                '-gravity', 'center',
                '-extent', $size,
                '-quality', (string) self::JPEG_QUALITY,
                $tmpOut,
            ]);

            // 写真変換と同じく UTF-8 対策: stderr はログのみ、例外は UTF-8 確定の固定文言。
            // Windows の cmd.exe が CP932 でエラーを返すと上位の json_encode で Malformed UTF-8 になる
            // ため、stderr を生で例外に乗せない。
            if ($result->failed()) {
                Log::error('MediaThumbnailService: magick 実行失敗', [
                    'exit_code' => $result->exitCode(),
                    'stderr' => $this->toUtf8($result->errorOutput()),
                    'original_path' => $originalPath,
                ]);
                throw new \RuntimeException(
                    "サムネイル生成に失敗しました（exit code {$result->exitCode()}）。詳細はサーバログを確認してください。"
                );
            }

            if (!FileFacade::exists($tmpOut)) {
                throw new \RuntimeException('サムネイルファイルが生成されませんでした。');
            }

            // 3. サムネイルをオブジェクトストレージに書き戻す
            //    キー: 原本の YYYYMM ディレクトリと同 uuid で thumbnails/ 配下に jpg として保存
            $thumbnailPath = $this->buildThumbnailPath($originalPath);
            $dir = pathinfo($thumbnailPath, PATHINFO_DIRNAME);
            $basename = pathinfo($thumbnailPath, PATHINFO_BASENAME);

            Storage::disk(self::STORAGE_DISK)->putFileAs($dir, new File($tmpOut), $basename);

            return $thumbnailPath;
        } finally {
            // 4. 一時ファイルは必ず削除（成功時も失敗時も）
            FileFacade::delete([$tmpIn, $tmpOut]);
        }
    }

    /**
     * 動画原本（mov/mp4）から 1 秒目のフレームを切り出し、200x200・余白付き jpeg サムネイルを生成し、
     * 保存後のストレージキーを返す
     *
     * 処理は2段構成：FFmpeg でフレームを無劣化 png として抽出 → ImageMagick で写真と完全に同じ
     * リサイズ・余白・JPEG エンコードを通す。これにより一覧で写真と動画のサムネイルが並んだとき
     * 見た目（余白の白・サイズ感・JPEG ノイズ）が厳密に揃う。
     *
     * @param string $originalPath 原本のストレージキー（例: media/202606/{uuid}.mov）
     * @return string サムネイルのストレージキー（例: thumbnails/202606/{uuid}.jpg）
     * @throws \RuntimeException 生成失敗時
     */
    public function generateVideoThumbnail(string $originalPath): string
    {
        $tmpDir = storage_path('app/' . self::TMP_DIR);
        FileFacade::ensureDirectoryExists($tmpDir);

        $tmpId = (string) Str::uuid();
        $originalExt = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'bin';
        $tmpIn = $tmpDir . DIRECTORY_SEPARATOR . $tmpId . '.' . $originalExt;
        // 中間ファイルは無劣化の png（jpg だと二重 JPEG 化で僅かに劣化）
        $tmpFrame = $tmpDir . DIRECTORY_SEPARATOR . $tmpId . '_frame.png';
        $tmpOut = $tmpDir . DIRECTORY_SEPARATOR . $tmpId . '.jpg';

        try {
            // 1. 原本をオブジェクトストレージから取得し、一時ファイルに書き出す
            $bytes = Storage::disk(self::STORAGE_DISK)->get($originalPath);
            if ($bytes === null) {
                throw new \RuntimeException("原本ファイルが取得できません: {$originalPath}");
            }
            FileFacade::put($tmpIn, $bytes);

            // 2a. FFmpeg で指定秒目のフレームを png として抽出（リサイズなし、無劣化）
            //     -ss は -i の後ろに置く（正確シーク。先頭からデコードして指定時刻に到達）。
            //     高速シーク（-i の前に -ss）はキーフレーム単位で飛ぶため、将来 -ss を中間値
            //     （0.5 等）にしたくなったとき指定どおりにならない罠がある。サムネ生成は登録時
            //     1 回だけなので、正確シークの速度低下は許容できる。
            //
            //     短尺動画フォールバック: メインの -ss 1（暗転回避）でフレームを取れなかった
            //     場合（IMG_0001.MOV のような Live Photo 等で映像ストリームが 1 秒未満のケース）、
            //     ffmpeg は exit code 0 で終わるが png を出力しない。これを「ファイル存在」で
            //     検知して -ss 0（先頭フレーム）で再実行する。短尺動画は先頭でも見られる絵に
            //     なることが多く、暗転リスクは少ない。
            $ffmpegPath = (string) config('media.ffmpeg_path', 'ffmpeg');
            $this->extractVideoFrame($ffmpegPath, $tmpIn, $tmpFrame, self::VIDEO_THUMBNAIL_SEEK_SECONDS, $originalPath);

            if (!FileFacade::exists($tmpFrame)) {
                Log::warning('MediaThumbnailService: 短尺動画と判定（-ss 1 でフレーム未出力）、-ss 0 でフォールバック', [
                    'original_path' => $originalPath,
                    'initial_seek_seconds' => self::VIDEO_THUMBNAIL_SEEK_SECONDS,
                ]);
                $this->extractVideoFrame($ffmpegPath, $tmpIn, $tmpFrame, 0, $originalPath);
            }

            if (!FileFacade::exists($tmpFrame)) {
                throw new \RuntimeException('抽出フレームが生成されませんでした（フォールバック後も失敗）。');
            }

            // 2b. 抽出フレームを ImageMagick でサムネイル化（写真と完全に同じコマンド）
            //     リサイズ・余白・JPEG 品質を写真と揃え、一覧で見た目を厳密に一致させる
            $size = self::THUMBNAIL_SIZE . 'x' . self::THUMBNAIL_SIZE;
            $magickPath = (string) config('media.magick_path', 'magick');
            $magickResult = Process::timeout(300)->run([
                $magickPath,
                $tmpFrame,
                '-auto-orient',
                '-resize', $size,
                '-background', self::BACKGROUND_COLOR,
                '-gravity', 'center',
                '-extent', $size,
                '-quality', (string) self::JPEG_QUALITY,
                $tmpOut,
            ]);

            if ($magickResult->failed()) {
                Log::error('MediaThumbnailService: magick サムネイル化失敗（動画フレーム）', [
                    'exit_code' => $magickResult->exitCode(),
                    'stderr' => $this->toUtf8($magickResult->errorOutput()),
                    'original_path' => $originalPath,
                ]);
                throw new \RuntimeException(
                    "サムネイル生成に失敗しました（exit code {$magickResult->exitCode()}）。詳細はサーバログを確認してください。"
                );
            }

            if (!FileFacade::exists($tmpOut)) {
                throw new \RuntimeException('サムネイルファイルが生成されませんでした。');
            }

            // 3. サムネイルをオブジェクトストレージに書き戻す（写真と同じキー設計）
            $thumbnailPath = $this->buildThumbnailPath($originalPath);
            $dir = pathinfo($thumbnailPath, PATHINFO_DIRNAME);
            $basename = pathinfo($thumbnailPath, PATHINFO_BASENAME);

            Storage::disk(self::STORAGE_DISK)->putFileAs($dir, new File($tmpOut), $basename);

            return $thumbnailPath;
        } finally {
            // 4. 一時ファイルは必ず削除（原本・中間フレーム・出力すべて）
            FileFacade::delete([$tmpIn, $tmpFrame, $tmpOut]);
        }
    }

    /**
     * 原本パスからサムネイルのキーを組み立てる
     *
     * 原本のディレクトリ階層（media/YYYYMM/）を thumbnails/YYYYMM/ に置換し、
     * 拡張子を .jpg に揃える。uuid は原本と同じなので原本との紐付けが目視で追える。
     *
     * 例: media/202606/{uuid}.heic → thumbnails/202606/{uuid}.jpg
     * 例: media/202606/{uuid}.jpg  → thumbnails/202606/{uuid}.jpg
     */
    private function buildThumbnailPath(string $originalPath): string
    {
        $name = pathinfo($originalPath, PATHINFO_FILENAME);
        $dir = pathinfo($originalPath, PATHINFO_DIRNAME);
        // dirname が '.' になるケースは想定外（storage_key は必ず media/YYYYMM/... 形式）
        // 'media/YYYYMM' → 'thumbnails/YYYYMM' に置換（先頭の 'media/' を切り替え）
        $thumbDir = preg_replace('#^media/#', 'thumbnails/', $dir);
        return $thumbDir . '/' . $name . '.jpg';
    }

    /**
     * ffmpeg で指定秒目のフレームを png として抽出する（generateVideoThumbnail のヘルパー）
     *
     * exit code 0 でも png が出力されないケース（シーク先がビデオストリーム範囲外、
     * IMG_0001.MOV のような短尺映像）があるため、結果は呼び出し側で FileFacade::exists で
     * 判定し、未出力ならフォールバックする設計。本メソッドは exit code 非0（真の失敗）のみ例外。
     *
     * @throws \RuntimeException ffmpeg が exit code 非0 で終了した場合
     */
    private function extractVideoFrame(string $ffmpegPath, string $tmpIn, string $tmpFrame, int $seekSeconds, string $originalPath): void
    {
        $result = Process::timeout(300)->run([
            $ffmpegPath,
            '-i', $tmpIn,
            '-ss', (string) $seekSeconds,
            '-frames:v', '1',
            '-y',
            $tmpFrame,
        ]);

        // UTF-8 対策: stderr はログのみ、例外は固定文言（写真サムネ・動画変換と同じ作法）
        if ($result->failed()) {
            Log::error('MediaThumbnailService: ffmpeg フレーム抽出失敗', [
                'exit_code' => $result->exitCode(),
                'stderr' => $this->toUtf8($result->errorOutput()),
                'original_path' => $originalPath,
                'seek_seconds' => $seekSeconds,
            ]);
            throw new \RuntimeException(
                "サムネイル生成に失敗しました（exit code {$result->exitCode()}）。詳細はサーバログを確認してください。"
            );
        }
    }

    /**
     * 外部プロセスの stdout/stderr を UTF-8 化する
     *
     * Windows の cmd.exe は CP932（Shift-JIS）で日本語エラーを返すため、そのまま
     * Log や JSON に渡すと Malformed UTF-8 で連鎖事故になる。Windows系の代表的な
     * エンコーディングからの推定変換を行い、UTF-8 として安全な文字列を返す。
     *
     * MediaConversionService に同じ実装がある。3つ目の Service が出てきたら trait 化を検討。
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
