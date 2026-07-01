<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * クライアント閲覧機能（柱2）— ログインコントローラ
 *
 * トレーナー用の web guard とは独立した client guard で認証する。
 * is_viewable=true のクライアントのみログイン可能（案X＝attempt 条件に含める）。
 * ロック管理・login_attempts・access_logs・last_login_at は持ち込まない（トレーナーから捨てる）。
 */
class LoginController extends Controller
{
    /**
     * クライアントログイン画面を表示（GET /client/login）
     */
    public function showLoginForm(): View
    {
        return view('client.login');
    }

    /**
     * クライアントログイン処理（POST /client/login）
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // is_viewable=true を attempt の条件に含めることで、閲覧未解放（false）は
        // 認証失敗と同じパスに集約される。メール存在確認耐性を優先する設計（案X）。
        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'is_viewable' => true,
        ];

        if (Auth::guard('client')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('client-portal.dashboard'));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);
    }
}
