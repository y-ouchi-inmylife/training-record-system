<?php

namespace App\Console\Commands;

use App\Models\AudioRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 保存期間を過ぎた音声ファイルを自動削除するコマンド
 *
 * スケジューラーから毎日午前3時に実行される。
 * 保存期間は --days オプションで指定する（デフォルト7日、範囲1〜30）。
 */
class DeleteExpiredAudioRecords extends Command
{
    protected $signature = 'audio-records:delete-expired {--days=7 : 保存期間（日数）。1〜30}';
    protected $description = '保存期間を過ぎた音声ファイルを自動削除';

    public function handle(): int
    {
        Log::info('[DeleteExpiredAudioRecords] 音声ファイル自動削除バッチを開始します');

        // コマンド引数から保存期間を取得
        $retentionDays = (int) $this->option('days');

        // 保存期間の範囲チェック（1〜30日）。範囲外は削除処理に進ませず異常終了する。
        // これは想定内のバリデーションエラーのため、例外ログ（error）ではなく warning に留める。
        if ($retentionDays < 1 || $retentionDays > 30) {
            $message = "[DeleteExpiredAudioRecords] 不正な保存期間が指定されました: {$retentionDays}（範囲は1〜30日）";
            $this->error('保存期間は1〜30日の範囲で指定してください。');
            Log::warning($message);

            return Command::FAILURE;
        }

        try {
            // 削除対象: 保存期間超過かつ音声ファイルがまだ残っているもの
            $expiredFiles = AudioRecord::where('created_at', '<', now()->subDays($retentionDays))
                ->whereNotNull('file_path')
                ->get();

            $deletedCount = 0;
            $deletedSize = 0;

            foreach ($expiredFiles as $file) {
                // 音声ファイルを削除
                if (Storage::exists($file->file_path)) {
                    $deletedSize += $file->file_size ?? 0;
                    Storage::delete($file->file_path);
                }

                // file_path を NULL に更新（文字起こし・要約は保持）
                $file->update(['file_path' => null]);
                $deletedCount++;
            }

            $sizeMb = round($deletedSize / 1024 / 1024, 2);
            $message = "[DeleteExpiredAudioRecords] 音声ファイル自動削除完了: {$deletedCount}件（{$sizeMb} MB）保存期間: {$retentionDays}日。文字起こし・要約データは保持";

            $this->info($message);
            Log::info($message);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $errorMessage = '[DeleteExpiredAudioRecords] エラー: ' . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage, ['exception' => $e]);

            return Command::FAILURE;
        }
    }
}
