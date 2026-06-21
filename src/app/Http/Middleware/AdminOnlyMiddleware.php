<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 「管理者のみ」（admin ロールのみ）のアクセスを許可するミドルウェア
 *
 * system_admin、staff、未認証はすべて 403。
 * 広義 admin（admin + system_admin）を許可したい場合は AdminMiddleware を使う。
 */
class AdminOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isAdminOnly()) {
            abort(403, 'この操作は管理者のみ実行できます。');
        }

        return $next($request);
    }
}
