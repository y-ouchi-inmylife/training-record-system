<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * ダッシュボード画面を表示
     */
    public function index(): View
    {
        // 主担当クライアントの基本条件
        $baseQuery = Client::where('primary_trainer_id', Auth::id())
            ->where(function ($query) {
                $query->whereHas('supportStatus', function ($q) {
                    $q->where('show_in_dashboard', true);
                })->orWhereNull('support_status_id');
            });

        // 総件数を取得
        $myClientsTotal = $baseQuery->count();

        // 主担当クライアント一覧を取得（最終トレーニング日が新しい順、NULLは最後、10件まで）
        $myClients = (clone $baseQuery)
            ->select('clients.*')
            ->selectSub(
                'SELECT MAX(training_date) FROM training_records WHERE training_records.client_id = clients.id',
                'last_training_date'
            )
            ->with(['supportStatus', 'trainingRecords' => function ($query) {
                $query->orderBy('training_date', 'desc')
                      ->orderBy('training_time', 'desc')
                      ->limit(1)
                      ->with(['trainer1', 'trainer2']);
            }])
            ->orderByRaw('CASE WHEN last_training_date IS NULL THEN 1 ELSE 0 END, last_training_date DESC')
            ->limit(10)
            ->get();

        return view('dashboard', compact('myClients', 'myClientsTotal'));
    }
}
