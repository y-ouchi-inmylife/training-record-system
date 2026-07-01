<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * クライアント閲覧機能（柱2）— ログアウトコントローラ
 *
 * client guard を明示指定してログアウトする。トレーナー側（web guard）は無影響。
 * access_logs はトレーナー操作履歴専用のため、ここには書かない。
 */
class LogoutController extends Controller
{
    /**
     * クライアントログアウト処理（POST /client/logout）
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client-portal.login');
    }
}
