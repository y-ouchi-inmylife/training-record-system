<?php

namespace App\Jobs;

use App\Models\MediaRecord;
use App\Services\MediaThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * メディアサムネイル生成ジョブ
 *
 * 原本から 200x200 のサムネイルを生成する。変換（表示用ファイル）の完了を待たないため、
 * 変換と独立に実行できる。開発は QUEUE_CONNECTION=sync で同期実行、本番はキューワーカーで
 * 非同期実行する想定。
 *
 * type に応じて写真（heic/jpeg/png 原本 → jpeg）/ 動画（mov/mp4 原本 → jpeg、FFmpeg で
 * フレーム抽出後 ImageMagick でサムネイル化）の生成メソッドを振り分ける。
 */
class GenerateThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public int $backoff = 60;

    public function __construct(
        private readonly int $mediaRecordId
    ) {}

    public function handle(MediaThumbnailService $thumbnailService): void
    {
        $mediaRecord = MediaRecord::find($this->mediaRecordId);

        if (!$mediaRecord) {
            Log::warning("GenerateThumbnailJob: メディアレコードが見つかりません (ID: {$this->mediaRecordId})");
            return;
        }

        // 既に別の状態に遷移している場合はスキップ（controller で processing にしてから dispatch）
        if ($mediaRecord->thumbnail_status !== MediaRecord::THUMBNAIL_PROCESSING) {
            Log::info("GenerateThumbnailJob: ステータスが processing ではないためスキップ (ID: {$this->mediaRecordId}, status: {$mediaRecord->thumbnail_status})");
            return;
        }

        try {
            // type で写真/動画を振り分け。default は DB CHECK 制約があるので実質到達しないが、
            // type 列挙が将来増えたとき気づけるよう保険として残す。
            $thumbnailPath = match ($mediaRecord->type) {
                MediaRecord::TYPE_PHOTO => $thumbnailService->generatePhotoThumbnail($mediaRecord->original_path),
                MediaRecord::TYPE_VIDEO => $thumbnailService->generateVideoThumbnail($mediaRecord->original_path),
                default => throw new \RuntimeException("未対応のメディア種別: {$mediaRecord->type}"),
            };

            $mediaRecord->update([
                'thumbnail_path' => $thumbnailPath,
                'thumbnail_status' => MediaRecord::THUMBNAIL_DONE,
            ]);

            Log::info("GenerateThumbnailJob: サムネイル生成完了 (ID: {$this->mediaRecordId})");

        } catch (\Throwable $e) {
            Log::error("GenerateThumbnailJob: サムネイル生成失敗 (ID: {$this->mediaRecordId}, 試行 {$this->attempts()}/{$this->tries}): {$e->getMessage()}");

            // 最終試行時のみエラーステータスに更新（ConvertMediaJob と同型）
            if ($this->attempts() >= $this->tries) {
                $mediaRecord->update([
                    'thumbnail_status' => MediaRecord::THUMBNAIL_ERROR,
                ]);
            }

            throw $e;
        }
    }
}
