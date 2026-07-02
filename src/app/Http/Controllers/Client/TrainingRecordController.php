<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MediaRecordController as TrainerMediaRecordController;
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
 * - 紐づくメディアはサムネイル URL とともに view に渡す。記録レベル本人認可を
 *   通った時点で「本人の記録に紐づくメディア」であることが保証されるため、
 *   サムネイル発行に個別のメディア認可は挟まない（再生 API 側で個別認可）
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
        // phase / updatedBy は意図的にロードしない（非開示）。mediaRecords は
        // belongsToMany 側で orderByPivot('sort_order') 済み＝sort_order 昇順で並ぶ。
        $trainingRecord->load(['client', 'trainingType', 'trainer1', 'trainer2', 'mediaRecords']);

        // メディアグリッド用の表示データ（presigned サムネイル URL を含む）。
        // 再生は /client/media/{id}/play を叩く（そちらでメディア単位の本人認可）。
        $thumbnailExpiresAt = now()->addMinutes(TrainerMediaRecordController::PLAY_URL_EXPIRES_MINUTES);
        $mediaItems = $trainingRecord->mediaRecords->map(function ($m) use ($thumbnailExpiresAt) {
            return [
                'id'               => $m->id,
                'type'             => $m->type,
                'displayTitle'     => $m->display_title,
                'thumbnailUrl'     => $m->temporaryThumbnailUrl($thumbnailExpiresAt),
                'conversionStatus' => $m->conversion_status,
            ];
        })->values()->all();

        return view('client.training-records.show', compact('trainingRecord', 'mediaItems'));
    }
}
