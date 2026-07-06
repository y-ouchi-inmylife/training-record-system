<?php

namespace App\Http\Middleware;

use App\Support\SessionRoleResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * リクエストの役割（トレーナー/クライアント）を判定し、
 * session.lifetime / session.cookie / session.domain を役割別に動的上書きする。
 *
 * StartSession より前に走る必要があるため、web(prepend) で登録する。
 * domain は role 側 config が null なら既存の session.domain（SESSION_DOMAIN）を維持。
 */
class ConfigureSessionByRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = SessionRoleResolver::isClient($request) ? 'client' : 'trainer';

        config([
            'session.lifetime' => (int) config("session_roles.{$role}.lifetime"),
            'session.cookie'   => config("session_roles.{$role}.cookie"),
            'session.domain'   => config("session_roles.{$role}.domain") ?? config('session.domain'),
        ]);

        return $next($request);
    }
}
