<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MediaRecordController as TrainerMediaRecordController;
use App\Models\MediaRecord;
use App\Services\MediaAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * クライアント閲覧機能（柱2）— メディア再生コントローラ
 *
 * メディアID直叩き経路の認可を担う。既存トレーナー側 MediaRecordController@play
 * は practitioners グループ内で所有者チェックを行わない前提のため流用不可。
 * 認可通過後の presigned URL 発行ロジックは既存と同等（同じ有効期限・同じディスク）。
 */
class MediaRecordController extends Controller
{
    public function play(MediaRecord $mediaRecord, MediaAccessService $access): JsonResponse
    {
        // メディア単位の本人認可：紐づくいずれかの記録が本人のものでなければ 403
        if (! $access->canClientView($mediaRecord, auth('client')->user())) {
            abort(403);
        }

        // 表示用ファイルが未生成（変換前・変換中・変換失敗）は 409。既存 play と同挙動
        if ($mediaRecord->display_path === null) {
            return response()->json([
                'error' => '表示用ファイルが未生成です。',
                'conversion_status' => $mediaRecord->conversion_status,
            ], 409);
        }

        $expiresAt = now()->addMinutes(TrainerMediaRecordController::PLAY_URL_EXPIRES_MINUTES);
        $url = Storage::disk(MediaRecord::STORAGE_DISK)
            ->temporaryUrl($mediaRecord->display_path, $expiresAt);

        return response()->json([
            'data' => [
                'url' => $url,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }
}
