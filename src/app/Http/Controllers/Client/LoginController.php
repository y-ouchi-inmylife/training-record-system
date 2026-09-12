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
 * 認証条件は email + password のみ。初回設定が未完了のクライアントは
 * password が NULL のため、通常の attempt でハッシュ照合に失敗し、
 * 「メールが登録されているかどうか」を露呈させずに済む。
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

        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
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
