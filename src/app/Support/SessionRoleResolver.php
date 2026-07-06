<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * リクエストがトレーナー側かクライアント側かを判定するユーティリティ。
 *
 * 判定方針:
 *   - 本番: ホスト一致（サブドメインが異なるため識別できる）
 *   - ローカル/共通: パス判定（トレーナー/クライアントとも localhost に解決されるため）
 *
 * セッション分離（ConfigureSessionByRole）で使うほか、後続の IP 制限・
 * リダイレクトでも共通利用の余地を残す。
 */
class SessionRoleResolver
{
    /**
     * リクエストがクライアント側か判定する。
     */
    public static function isClient(Request $request): bool
    {
        $clientHost  = (string) config('subdomain.client_host');
        $trainerHost = (string) config('subdomain.trainer_host');

        // 本番のようにトレーナーとクライアントのホストが分かれている場合はホスト一致で判定。
        // ローカルで両方 localhost の場合はホスト一致を無効化し、パス判定に委ねる。
        if ($clientHost !== '' && $clientHost !== $trainerHost && $request->getHost() === $clientHost) {
            return true;
        }

        // クライアント側パス（client-portal と client-intake）。
        // password-setup は client-portal/password-setup/* なので client-portal/* に含まれる。
        return $request->is('client-portal/*') || $request->is('client-intake/*');
    }

    /**
     * リクエストがトレーナー側か判定する（isClient の否）。
     */
    public static function isTrainer(Request $request): bool
    {
        return ! self::isClient($request);
    }
}
