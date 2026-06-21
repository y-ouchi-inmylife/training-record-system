<?php

namespace App\Http\Middleware;

use App\Models\AccessLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * トレーナー操作履歴記録ミドルウェア
 */
class LogAccess
{
    /** ログ対象のルート名と操作名のマッピング */
    private const ACTION_MAP = [
        'clients.show' => 'view_client',
        'clients.store' => 'create_client',
        'clients.update' => 'edit_client',
        'clients.destroy' => 'delete_client',
        'counseling-records.show' => 'view_counseling_record',
        'counseling-records.store' => 'create_counseling_record',
        'counseling-records.update' => 'edit_counseling_record',
        'counseling-records.destroy' => 'delete_counseling_record',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (auth()->check() && $request->route()) {
            $this->logAccess($request);
        }

        return $response;
    }

    private function logAccess(Request $request): void
    {
        $routeName = $request->route()->getName();
        if (!$routeName) {
            return;
        }

        $action = self::ACTION_MAP[$routeName] ?? null;
        if (!$action) {
            return;
        }

        // 対象IDを取得
        $targetType = null;
        $targetId = null;

        if (str_contains($action, 'client') && !str_contains($action, 'counseling')) {
            $targetType = 'Client';
            $param = $request->route('client');
            $targetId = is_object($param) ? $param->id : $param;
        } elseif (str_contains($action, 'counseling_record')) {
            $targetType = 'CounselingRecord';
            $param = $request->route('counseling_record') ?? $request->route('counselingRecord');
            $targetId = is_object($param) ? $param->id : $param;
        }

        AccessLog::create([
            'counselor_id' => auth()->id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);
    }
}
