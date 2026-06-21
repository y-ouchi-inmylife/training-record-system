<?php

namespace App\Http\Controllers;

use App\Models\AudioRecord;
use Illuminate\Http\Request;

/**
 * 音声データ管理状況コントローラー
 *
 * 音声ファイルの容量管理に必要な統計・一覧を表示する。
 */
class UsageStatsController extends Controller
{
    /**
     * 音声データ管理状況画面を表示
     */
    public function index(Request $request)
    {
        // 実ファイルが存在するレコードに絞り込み（file_pathあり かつ file_size > 0）
        $baseQuery = AudioRecord::whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->where('file_size', '>', 0);

        // 全体統計
        $totalStats = [
            'total_files' => (clone $baseQuery)->count(),
            'total_size' => (clone $baseQuery)->sum('file_size'),
        ];

        // 音声ファイル一覧（新しい順、10件/ページ）
        $audioRecords = $baseQuery
            ->with('counselor')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('usage-stats.index', compact('totalStats', 'audioRecords'));
    }
}
