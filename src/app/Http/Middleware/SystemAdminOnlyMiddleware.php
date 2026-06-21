<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 「システム管理者のみ」（system_admin ロールのみ）のアクセスを許可するミドルウェア
 *
 * admin、staff、未認証はすべて 403。
 * 広義 admin（admin + system_admin）を許可したい場合は AdminMiddleware を使う。
 * admin のみを許可したい場合は AdminOnlyMiddleware を使う。
 */
class SystemAdminOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isSystemAdmin()) {
            abort(403, 'この操作はシステム管理者のみ実行できます。');
        }

        return $next($request);
    }
}
