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
        'training-records.show' => 'view_training_record',
        'training-records.store' => 'create_training_record',
        'training-records.update' => 'edit_training_record',
        'training-records.destroy' => 'delete_training_record',
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

        if (str_contains($action, 'client')) {
            $targetType = 'Client';
            $param = $request->route('client');
            $targetId = is_object($param) ? $param->id : $param;
        } elseif (str_contains($action, 'training_record')) {
            $targetType = 'TrainingRecord';
            $param = $request->route('training_record') ?? $request->route('trainingRecord');
            $targetId = is_object($param) ? $param->id : $param;
        }

        AccessLog::create([
            'trainer_id' => auth()->id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);
    }
}
