<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * パスワード変更必須チェックミドルウェア
 */
class CheckPasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->must_change_password) {
            // パスワード変更ルートとログアウトルートは除外
            if (!$request->routeIs('password.change') &&
                !$request->routeIs('password.update') &&
                !$request->routeIs('logout')) {
                return redirect()->route('password.change')
                    ->with('warning', '初回ログインのため、パスワードを変更してください。');
            }
        }

        return $next($request);
    }
}
