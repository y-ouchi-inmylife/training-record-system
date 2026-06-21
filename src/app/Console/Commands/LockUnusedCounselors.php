<?php

namespace App\Console\Commands;

use App\Models\Counselor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 未使用（発行後一度もログインがない）カウンセラーを自動ロックするコマンド
 *
 * 使用例:
 *   php artisan counselors:lock-unused
 *   php artisan counselors:lock-unused --days=14
 */
class LockUnusedCounselors extends Command
{
    protected $signature = 'counselors:lock-unused {--days=7 : 作成日からの経過日数のしきい値}';

    protected $description = '発行後一度もログインがないカウンセラーを作成日から指定日数経過でロックします';

    public function handle(): int
    {
        Log::info('[LockUnusedCounselors] アカウントロック（未使用）バッチを開始します');

        try {
            $days = (int) $this->option('days');
            $threshold = Carbon::now()->subDays($days);

            $this->info("発行後一度もログインがなく、作成から{$days}日以上経過したカウンセラーをチェックします...");

            // 新規発行後に使われないままのアカウント対策のバッチ。
            // 一度もログインしていない（last_login_at が null の）アカウントのうち、作成日時が指定日数以上前のものを対象とする。
            // ログイン履歴のあるアカウント（退職・休職者対策）は counselors:lock-inactive の領域のため対象外とする（No.200）。
            $candidates = Counselor::where('is_active', true)
                ->where('is_locked', false)
                ->where('role', '!=', 'system_admin') // system_adminは自動ロック対象外
                ->whereNull('last_login_at') // 一度もログインしていないアカウントのみを対象とする
                ->where('created_at', '<', $threshold)
                ->get();

            $count = 0;
            foreach ($candidates as $counselor) {
                // 最後の有効な管理者（admin）はロックしない（system_adminは別途除外済み）
                if ($counselor->role === 'admin') {
                    $activeAdminCount = Counselor::where('role', 'admin')
                        ->where('is_active', true)
                        ->where('is_locked', false)
                        ->count();

                    if ($activeAdminCount <= 1) {
                        $this->warn("有効な管理者が1人のため、{$counselor->name} をロックしませんでした。");

                        continue;
                    }
                }

                $counselor->update(['is_locked' => true]);
                $count++;

                $createdAt = $counselor->created_at?->format('Y-m-d') ?? '不明';
                $this->info("  ロック: {$counselor->name}（作成日: {$createdAt}）");
            }

            $message = "{$count}件のカウンセラーをロックしました。";
            $this->info($message);
            Log::info("[LockUnusedCounselors] {$message}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $errorMessage = '[LockUnusedCounselors] エラー: ' . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage, ['exception' => $e]);

            return Command::FAILURE;
        }
    }
}
