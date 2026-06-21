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
        $baseQuery = Client::where('primary_counselor_id', Auth::id())
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
                'SELECT MAX(consultation_date) FROM counseling_records WHERE counseling_records.client_id = clients.id',
                'last_consultation_date'
            )
            ->with(['supportStatus', 'counselingRecords' => function ($query) {
                $query->orderBy('consultation_date', 'desc')
                      ->orderBy('consultation_time', 'desc')
                      ->limit(1)
                      ->with(['counselor1', 'counselor2']);
            }])
            ->orderByRaw('CASE WHEN last_consultation_date IS NULL THEN 1 ELSE 0 END, last_consultation_date DESC')
            ->limit(10)
            ->get();

        return view('dashboard', compact('myClients', 'myClientsTotal'));
    }
}
