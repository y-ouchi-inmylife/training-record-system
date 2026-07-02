<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MediaRecord;

/**
 * クライアント閲覧機能（柱2）— メディア閲覧認可サービス
 *
 * あるメディアが、あるクライアント本人のトレーニング記録に紐づくか判定する。
 * 非排他：本人の記録に「1件でも」紐づけば true。孤児メディア（どの記録にも
 * 紐づかない）は exists で自動的に false になる。
 */
class MediaAccessService
{
    /**
     * 指定メディアが、指定クライアント本人のトレーニング記録に紐づくか。
     *
     * 中間テーブル（media_record_training_record）経由の many-to-many join で
     * training_records.client_id を照合する。テーブル名を明示するのは、
     * 結合先の training_records と中間テーブル双方に含まれ得るカラム名の
     * 曖昧参照を避けるため。
     */
    public function canClientView(MediaRecord $media, Client $client): bool
    {
        return $media->trainingRecords()
            ->where('training_records.client_id', $client->id)
            ->exists();
    }
}
