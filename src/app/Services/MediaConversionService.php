<?php

namespace App\Services;

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
 * 2b-1: 写真変換（heic/heif → jpeg）のみ実装。
 * 2b-2 以降で動画変換（mov → mp4）メソッドを追加予定。
 */
class MediaConversionService
{
    // 表示用 jpeg の品質（画面表示用の標準品質。原本は残すので印刷品質は原本で確保）
    private const JPEG_QUALITY = 85;

    // オブジェクトストレージのディスク名（MediaRecordController と揃える）
    private const STORAGE_DISK = 'sakura';

    // 一時ファイル置き場（storage/app 配下は git 管理外）
    private const TMP_DIR = 'tmp/conversion';

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
            // 1. 原本をオブジェクトストレージから取得し、一時ファイルに書き出す
            $bytes = Storage::disk(self::STORAGE_DISK)->get($originalPath);
            if ($bytes === null) {
                throw new \RuntimeException("原本ファイルが取得できません: {$originalPath}");
            }
            FileFacade::put($tmpIn, $bytes);

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
            $displayPath = $this->buildDisplayPath($originalPath);
            $dir = pathinfo($displayPath, PATHINFO_DIRNAME);
            $basename = pathinfo($displayPath, PATHINFO_BASENAME);

            Storage::disk(self::STORAGE_DISK)->putFileAs($dir, new File($tmpOut), $basename);

            return $displayPath;
        } finally {
            // 4. 一時ファイルは必ず削除（成功時も失敗時も）
            FileFacade::delete([$tmpIn, $tmpOut]);
        }
    }

    /**
     * 原本パスから表示用ファイル（jpg）のキーを組み立てる
     *
     * 例: media/202606/{uuid}.heic → media/202606/{uuid}.jpg
     */
    private function buildDisplayPath(string $originalPath): string
    {
        $dir = pathinfo($originalPath, PATHINFO_DIRNAME);
        $name = pathinfo($originalPath, PATHINFO_FILENAME);
        // dirname が '.' になるケースは想定外（storage_key は必ず media/YYYYMM/... 形式）
        return ($dir === '.' || $dir === '') ? $name . '.jpg' : $dir . '/' . $name . '.jpg';
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
