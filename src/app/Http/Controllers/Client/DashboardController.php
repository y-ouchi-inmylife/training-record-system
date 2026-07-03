<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MediaRecordController as TrainerMediaRecordController;
use Illuminate\Contracts\View\View;

/**
 * クライアント閲覧機能（柱2）— ダッシュボードコントローラ
 *
 * ログイン中のクライアント自身のトレーニング記録一覧を表示する。
 * 記録詳細（S-1404）への行クリック遷移は view 側で route() する。
 * 併せて、本人の全記録に紐づくメディアをサムネイルギャラリーとして表示する
 * ため、記録×メディアを平坦化した配列を view に渡す。
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        // 自分の記録を日付の新しい順（降順）で取得。
        // 一覧表示に使うリレーション（担当1・担当2・トレーニング内容）に加え、
        // メディアギャラリー展開のため mediaRecords も eager load（N+1回避）。
        // phase / updatedBy はクライアント非表示のため意図的にロードしない。
        $trainingRecords = auth('client')->user()
            ->trainingRecords()
            ->with(['trainer1', 'trainer2', 'trainingType', 'mediaRecords'])
            ->withCount('mediaRecords')
            ->orderByDesc('training_date')
            ->get();

        // メディアギャラリー用に「記録×メディア」を平坦化（案A）。
        // 同じメディアが複数記録に紐づく場合は記録ごとに複数回出現する。
        // 各エントリに紐づく記録の training_date を添える（サムネイル下に表示）。
        // 記録内は mediaRecords() の orderByPivot('sort_order') により昇順で取れる。
        // presigned サムネイル URL は HMAC ローカル計算で高速（S3 往復なし）。
        $thumbnailExpiresAt = now()->addMinutes(TrainerMediaRecordController::PLAY_URL_EXPIRES_MINUTES);
        $mediaItems = [];
        foreach ($trainingRecords as $rec) {
            foreach ($rec->mediaRecords as $m) {
                $mediaItems[] = [
                    'id'               => $m->id,
                    'type'             => $m->type,
                    'displayTitle'     => $m->display_title,
                    'thumbnailUrl'     => $m->temporaryThumbnailUrl($thumbnailExpiresAt),
                    'conversionStatus' => $m->conversion_status,
                    'trainingDate'     => $rec->training_date->format('Y/m/d'),
                ];
            }
        }

        return view('client.dashboard', compact('trainingRecords', 'mediaItems'));
    }
}
