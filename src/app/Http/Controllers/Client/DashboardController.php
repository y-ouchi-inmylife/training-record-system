<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * クライアント閲覧機能（柱2）— ダッシュボードコントローラ
 *
 * 段3では氏名を表示する挨拶画面を返す。
 * 記録・メディアへの導線は塊F・E詳細で追加する。
 * ログイン中のクライアントはビュー側で auth('client')->user() で取得する（トレーナー側と同流儀）。
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('client.dashboard');
    }
}
