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
        // 公開クライアント登録ルートはIP制限対象外（No.193）
        // クライアント本人が機関の許可IP外（自宅・スマホ等）からアクセスするため
        if ($request->routeIs('client-intake.*')) {
            return $next($request);
        }

        // 柱2: クライアント閲覧機能もIP制限対象外（クライアント本人が自宅・スマホから利用するため）
        if ($request->routeIs('client-portal.*')) {
            return $next($request);
        }

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
