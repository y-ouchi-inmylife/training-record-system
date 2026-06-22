<?php

namespace App\Jobs;

use App\Models\AudioRecord;
use App\Services\SummarizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 文字起こしテキストの要約ジョブ
 *
 * Laravelキューで非同期実行される。
 * Claude APIにテキストを送信し、要約結果をDBに保存する。
 *
 */
class SummarizeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * ジョブを試行する回数
     */
    public int $tries = 3;

    /**
     * ジョブがタイムアウトするまでの秒数
     */
    public int $timeout = 600;

    /**
     * リトライ間隔（秒）
     */
    public int $backoff = 60;

    public function __construct(
        private readonly int $audioRecordId
    ) {}

    public function handle(SummarizationService $summarizationService): void
    {
        $audioRecord = AudioRecord::find($this->audioRecordId);

        if (!$audioRecord) {
            Log::warning("SummarizeJob: 音声ファイルが見つかりません (ID: {$this->audioRecordId})");
            return;
        }

        // 既に別の状態に遷移している場合はスキップ
        if ($audioRecord->status !== AudioRecord::STATUS_SUMMARIZING) {
            Log::info("SummarizeJob: ステータスが summarizing ではないためスキップ (ID: {$this->audioRecordId}, status: {$audioRecord->status})");
            return;
        }

        // 文字起こしテキストが存在するか確認
        if (empty($audioRecord->transcription_text)) {
            Log::error("SummarizeJob: 文字起こしテキストが存在しません (ID: {$this->audioRecordId})");
            $audioRecord->update(['status' => AudioRecord::STATUS_ERROR]);
            return;
        }

        try {
            $summary = $summarizationService->summarize($audioRecord->transcription_text);

            $audioRecord->update([
                'summary_text' => $summary,
                'status' => AudioRecord::STATUS_COMPLETED,
                'summarized_at' => now(),
            ]);

            Log::info("SummarizeJob: 要約完了 (ID: {$this->audioRecordId})");

        } catch (\Throwable $e) {
            Log::error("SummarizeJob: 要約失敗 (ID: {$this->audioRecordId}, 試行 {$this->attempts()}/{$this->tries}): {$e->getMessage()}");

            // 最終試行時のみエラーステータスに更新
            if ($this->attempts() >= $this->tries) {
                $audioRecord->update([
                    'status' => AudioRecord::STATUS_ERROR,
                ]);
            }

            throw $e;
        }
    }
}
