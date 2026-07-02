<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * クライアント閲覧機能（柱2）— ダッシュボードコントローラ
 *
 * ログイン中のクライアント自身のトレーニング記録一覧を表示する。
 * 記録詳細（S-1404）への行クリック遷移は view 側で route() する。
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        // 自分の記録をトレーニング日の新しい順（降順）で取得。
        // 一覧表示に使うリレーション（担当1・担当2・トレーニング内容）のみ eager load。
        // phase / updatedBy はクライアント非表示のため意図的にロードしない。
        $trainingRecords = auth('client')->user()
            ->trainingRecords()
            ->with(['trainer1', 'trainer2', 'trainingType'])
            ->orderByDesc('training_date')
            ->get();

        return view('client.dashboard', compact('trainingRecords'));
    }
}
