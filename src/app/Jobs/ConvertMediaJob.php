<?php

namespace App\Jobs;

use App\Models\MediaRecord;
use App\Services\MediaConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * メディア表示用変換ジョブ
 *
 * 原本（heic/heif/mov）→ 表示用（jpeg/mp4）の変換を実行する。
 * 開発は QUEUE_CONNECTION=sync で同期実行、本番はキューワーカーで非同期実行する想定。
 *
 * type に応じて写真（heic/heif → jpeg）/ 動画（mov → mp4）の変換メソッドを振り分ける。
 */
class ConvertMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public int $backoff = 60;

    public function __construct(
        private readonly int $mediaRecordId
    ) {}

    public function handle(MediaConversionService $conversionService): void
    {
        $mediaRecord = MediaRecord::find($this->mediaRecordId);

        if (!$mediaRecord) {
            Log::warning("ConvertMediaJob: メディアレコードが見つかりません (ID: {$this->mediaRecordId})");
            return;
        }

        // 既に別の状態に遷移している場合はスキップ（controller で processing にしてから dispatch）
        if ($mediaRecord->conversion_status !== MediaRecord::CONVERSION_PROCESSING) {
            Log::info("ConvertMediaJob: ステータスが processing ではないためスキップ (ID: {$this->mediaRecordId}, status: {$mediaRecord->conversion_status})");
            return;
        }

        try {
            // type で写真/動画を振り分け。default は DB CHECK 制約があるので実質到達しないが、
            // type 列挙が将来増えたとき気づけるよう保険として残す。
            $displayPath = match ($mediaRecord->type) {
                MediaRecord::TYPE_PHOTO => $conversionService->convertPhotoToJpeg($mediaRecord->original_path),
                MediaRecord::TYPE_VIDEO => $conversionService->convertVideoToMp4($mediaRecord->original_path),
                default => throw new \RuntimeException("未対応のメディア種別: {$mediaRecord->type}"),
            };

            $mediaRecord->update([
                'display_path' => $displayPath,
                'conversion_status' => MediaRecord::CONVERSION_DONE,
            ]);

            Log::info("ConvertMediaJob: 変換完了 (ID: {$this->mediaRecordId})");

        } catch (\Throwable $e) {
            Log::error("ConvertMediaJob: 変換失敗 (ID: {$this->mediaRecordId}, 試行 {$this->attempts()}/{$this->tries}): {$e->getMessage()}");

            // 最終試行時のみエラーステータスに更新（音声ジョブと同型）
            if ($this->attempts() >= $this->tries) {
                $mediaRecord->update([
                    'conversion_status' => MediaRecord::CONVERSION_ERROR,
                ]);
            }

            throw $e;
        }
    }
}
