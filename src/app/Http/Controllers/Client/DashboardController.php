<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * クライアント閲覧機能（柱2）— ダッシュボードコントローラ
 *
 * 段1では auth:client の骨格確認用プレースホルダを返す。
 * ダッシュボードの中身（一覧・メディア導線）は段3で実装する。
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('client.dashboard-placeholder');
    }
}
