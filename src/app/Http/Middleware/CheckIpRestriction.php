<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * IPアドレス制限ミドルウェア
 */
class CheckIpRestriction
{
    /**
     * リクエストを処理
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 本ミドルウェアはトレーナー用サブドメインのルートグループにのみ付与される。
        // クライアント側（client-portal.* / client-intake.*）は構造的に対象外のため、
        // ここでのルート名バイパスは持たない。

        // ログイン・ログアウトルートは除外（誰でもアクセス可能）
        // POST /login にはルート名がないため、パスでも判定する
        if ($request->routeIs('login') || $request->routeIs('logout') || $request->is('login')) {
            return $next($request);
        }

        // システム管理者は制限対象外（ログイン後）
        if (auth()->check() && auth()->user()->role === 'system_admin') {
            return $next($request);
        }

        $clientIp = $request->ip();

        // ローカルホストは常に許可（開発環境用）
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return $next($request);
        }

        if (!SystemSetting::isIpAllowed($clientIp)) {
            abort(403, 'アクセスが許可されていないIPアドレスからの接続です。管理者にお問い合わせください。');
        }

        return $next($request);
    }
}
