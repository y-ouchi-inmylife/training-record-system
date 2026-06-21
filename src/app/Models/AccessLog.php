<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * トレーナー操作履歴モデル
 */
class AccessLog extends Model
{
    protected $fillable = [
        'counselor_id',
        'action',
        'target_type',
        'target_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * 操作名 → 日本語ラベルのマッピング
     *
     * アクセサ（getActionLabelAttribute）と Blade の操作フィルターの
     * 両方からこの定義を参照することで、ラベル定義を1箇所に集約する。
     */
    public static function actionLabels(): array
    {
        return [
            'login' => 'ログイン',
            'logout' => 'ログアウト',
            'view_client' => 'クライアント詳細',
            'edit_client' => 'クライアント編集',
            'create_client' => 'クライアント登録',
            'delete_client' => 'クライアント削除',
            'view_counseling_record' => 'トレーニング記録詳細',
            'edit_counseling_record' => 'トレーニング記録編集',
            'create_counseling_record' => 'トレーニング記録登録',
            'delete_counseling_record' => 'トレーニング記録削除',
        ];
    }

    /**
     * target_type → 日本語ラベルのマッピング
     *
     * アクセサ（getTargetLabelAttribute）から参照される。
     * 単一の真実の源として、この配列のみメンテナンスすればよい。
     */
    public static function targetLabels(): array
    {
        return [
            'Client' => 'クライアント',
            'CounselingRecord' => 'トレーニング記録',
        ];
    }

    /**
     * 操作名の日本語ラベル
     */
    public function getActionLabelAttribute(): string
    {
        return self::actionLabels()[$this->action] ?? $this->action;
    }

    /**
     * target_type の日本語ラベル
     *
     * target_type が null の場合は null を返す（ログインなど対象なしのケース）。
     * マッピングに無い値の場合は元の target_type を返す（フォールバック）。
     */
    public function getTargetLabelAttribute(): ?string
    {
        return $this->target_type
            ? (self::targetLabels()[$this->target_type] ?? $this->target_type)
            : null;
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(Counselor::class);
    }
}
