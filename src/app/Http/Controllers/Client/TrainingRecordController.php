<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\TrainingRecord;
use Illuminate\Contracts\View\View;

/**
 * クライアント閲覧機能（柱2）— トレーニング記録詳細コントローラ
 *
 * ログイン中のクライアントが「自分の」トレーニング記録の詳細を閲覧する。
 * トレーナー側 TrainingRecordController::show とは別実装：
 * - 本人認可（client_id 照合）で他人の記録は 403
 * - 所感（impression）・フェーズ・最終更新者などクライアント非開示情報は
 *   ロード・受け渡ししない（漏れを構造的に防ぐ）
 * - メディアは段2で追加するため、ここではロードしない
 */
class TrainingRecordController extends Controller
{
    public function show(TrainingRecord $trainingRecord): View
    {
        // 本人認可：対象記録がログイン中クライアント自身のものでなければ 403
        if ($trainingRecord->client_id !== auth('client')->id()) {
            abort(403);
        }

        // 表示に必要なリレーションのみロードする。
        // phase / updatedBy / mediaRecords は意図的にロードしない（非開示・段2の切り分け）。
        $trainingRecord->load(['client', 'trainingType', 'trainer1', 'trainer2']);

        return view('client.training-records.show', compact('trainingRecord'));
    }
}
