<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 実務カウンセラー（admin + staff）のみアクセスを許可するミドルウェア
 *
 * system_admin、未認証ユーザーはすべて 403。
 */
class PractitionersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isPractitioner()) {
            abort(403, 'この操作は実務カウンセラーのみ実行できます。');
        }

        return $next($request);
    }
}
