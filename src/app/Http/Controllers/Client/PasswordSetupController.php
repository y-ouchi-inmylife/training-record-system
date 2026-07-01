<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientPasswordSetupToken;
use App\Rules\StrongPassword;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * クライアントパスワード設定コントローラ（柱2 塊D 段2）
 *
 * 認証不要の公開画面。クライアントが招待メールで受け取ったURLからアクセスする。
 * client guard のログイン状態には無関係で、auth:client ミドルウェアは適用されない。
 * 手本は ClientIntakeController の showByToken/storeByToken。
 */
class PasswordSetupController extends Controller
{
    /**
     * トークンを検証し、パスワード設定画面を表示（GET /client/password-setup/{token}）
     */
    public function showByToken(string $token): View
    {
        $tokenRecord = ClientPasswordSetupToken::where('token', $token)->first();

        if (!$tokenRecord) {
            return view('client.password-setup.invalid-token', [
                'title' => 'このURLは無効です',
                'message' => 'URLが間違っているか、削除された可能性があります。担当のトレーナーにお問い合わせください。',
            ]);
        }

        if ($tokenRecord->isExpired()) {
            return view('client.password-setup.invalid-token', [
                'title' => 'このURLは期限切れです',
                'message' => 'このURLの有効期限が切れています。担当のトレーナーにお問い合わせください。改めて招待メールをお送りします。',
            ]);
        }

        if ($tokenRecord->is_used) {
            return view('client.password-setup.invalid-token', [
                'title' => 'このURLは既に使用されています',
                'message' => 'このURLで既にパスワードが設定されています。ログイン画面からログインしてください。',
            ]);
        }

        return view('client.password-setup.index', [
            'token' => $token,
            'client' => $tokenRecord->client,
        ]);
    }

    /**
     * パスワードを設定する（POST /client/password-setup/{token}）
     */
    public function storeByToken(Request $request, string $token): View|RedirectResponse
    {
        // トークン再検証（レース対策）
        $tokenRecord = ClientPasswordSetupToken::where('token', $token)->first();
        if (!$tokenRecord || $tokenRecord->isExpired() || $tokenRecord->is_used) {
            return view('client.password-setup.invalid-token', [
                'title' => 'このURLは無効です',
                'message' => 'URLが無効か、既に使用されています。担当のトレーナーにお問い合わせください。',
            ]);
        }

        $request->validate([
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ], [
            'password.required' => 'パスワードを入力してください。',
            'password.confirmed' => 'パスワード（確認）が一致しません。',
        ]);

        DB::transaction(function () use ($request, $tokenRecord) {
            $tokenRecord->client->update([
                'password' => $request->input('password'),
            ]);
            $tokenRecord->update(['is_used' => true]);
        });

        return redirect()->route('client-portal.login')
            ->with('success', 'パスワードを設定しました。ログインしてください。');
    }
}
