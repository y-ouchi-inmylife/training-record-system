<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * トレーナー操作履歴管理コントローラー
 */
class AccessLogController extends Controller
{
    /**
     * トレーナー操作履歴一覧画面
     */
    public function index(Request $request): View
    {
        // 日付フィルタの相関チェック（開始日 ≦ 終了日）
        $request->validate(
            [
                'date_from' => 'nullable|date',
                'date_to'   => 'nullable|date|after_or_equal:date_from',
            ],
            [
                'date_to.after_or_equal' => '開始日は終了日以前の日付を指定してください',
            ]
        );

        $query = AccessLog::with('counselor')
            ->orderBy('created_at', 'desc');

        // トレーナーフィルター
        if ($request->filled('counselor_id')) {
            $query->where('counselor_id', $request->input('counselor_id'));
        }

        // 操作フィルター
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // 期間フィルター
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate(50)->withQueryString();
        $counselors = Trainer::orderBy('name')->get();

        return view('access-logs.index', compact('logs', 'counselors'));
    }
}
