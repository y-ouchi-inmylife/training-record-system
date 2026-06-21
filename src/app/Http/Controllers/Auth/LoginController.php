<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Counselor;
use App\Models\LoginAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** 連続失敗でロックするまでの回数 */
    private const MAX_ATTEMPTS = 5;

    /**
     * ログイン画面を表示
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * ログイン処理
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login_id' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $userId = $request->input('login_id');
        $password = $request->input('password');
        $ipAddress = $request->ip();

        // トレーナーを検索
        $counselor = Counselor::where('login_id', $userId)->first();

        // アカウント無効化チェック
        if ($counselor && !$counselor->is_active) {
            $this->recordLoginAttempt($counselor->id, $userId, $ipAddress, false);

            return back()
                ->withInput($request->only('login_id'))
                ->withErrors(['login_id' => 'このアカウントは無効化されています。管理者にお問い合わせください。']);
        }

        // アカウントロック中かチェック
        if ($counselor && $counselor->is_locked) {
            $this->recordLoginAttempt($counselor->id, $userId, $ipAddress, false);

            return back()
                ->withInput($request->only('login_id'))
                ->withErrors(['login_id' => 'アカウントがロックされています。管理者に連絡してください。']);
        }

        // 認証チェック
        if ($counselor && Hash::check($password, $counselor->password)) {
            // ログイン成功
            $this->recordLoginAttempt($counselor->id, $userId, $ipAddress, true);

            // 最終ログイン日時を記録
            $counselor->update(['last_login_at' => now()]);

            // トレーナー操作履歴に記録
            AccessLog::create([
                'counselor_id' => $counselor->id,
                'action' => 'login',
                'ip_address' => $ipAddress,
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            ]);

            Auth::login($counselor);
            $request->session()->regenerate();

            // システム管理者は音声ファイル一覧（S-0701）、それ以外はダッシュボード（S-0101）へ
            $home = $counselor->isSystemAdmin() ? '/usage-stats' : '/dashboard';

            return redirect()->intended($home);
        }

        // ログイン失敗
        $this->recordLoginAttempt($counselor?->id, $userId, $ipAddress, false);

        // 連続失敗回数を確認してロック
        if ($counselor) {
            $this->checkAndLockAccount($counselor);
        }

        return back()
            ->withInput($request->only('login_id'))
            ->withErrors(['login_id' => 'ログインIDまたはパスワードが正しくありません。']);
    }

    /**
     * ログイン試行を記録
     */
    private function recordLoginAttempt(
        ?int $counselorId,
        string $loginIdInput,
        ?string $ipAddress,
        bool $success,
    ): void {
        LoginAttempt::create([
            'counselor_id' => $counselorId,
            'login_id_input' => $loginIdInput,
            'ip_address' => $ipAddress,
            'attempted_at' => now(),
            'success' => $success,
        ]);
    }

    /**
     * 連続失敗回数を確認し、上限に達したらアカウントをロック
     */
    private function checkAndLockAccount(Counselor $counselor): void
    {
        // 直近の連続失敗回数を取得（最後の成功以降の失敗数）
        $lastSuccess = LoginAttempt::where('counselor_id', $counselor->id)
            ->where('success', true)
            ->orderByDesc('attempted_at')
            ->value('attempted_at');

        $query = LoginAttempt::where('counselor_id', $counselor->id)
            ->where('success', false);

        if ($lastSuccess) {
            $query->where('attempted_at', '>', $lastSuccess);
        }

        $failCount = $query->count();

        if ($failCount >= self::MAX_ATTEMPTS) {
            $counselor->update(['is_locked' => true]);
        }
    }
}
