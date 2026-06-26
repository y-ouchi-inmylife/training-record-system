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
 * 2b-1: 写真変換（heic/heif → jpeg）のみ対応。動画変換は2b-2で同骨格に分岐追加予定。
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
            // 2b-1: 写真のみ対応。動画は2b-2で追加する分岐の入り口を明示しておく
            if ($mediaRecord->type !== MediaRecord::TYPE_PHOTO) {
                throw new \RuntimeException("動画変換は未実装です（2b-2で対応予定）。type: {$mediaRecord->type}");
            }

            $displayPath = $conversionService->convertPhotoToJpeg($mediaRecord->original_path);

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
