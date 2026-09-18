<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * トレーニーモデル（D-0700）
 *
 * 会員に紐づくトレーニング対象（当面は犬）。トレーニング記録（training_records）
 * は会員単位で、トレーニー単位ではない（設計書 requirements.md 6-16 参照）。
 */
class Trainee extends Model
{
    /*
    |--------------------------------------------------------------------------
    | 性別（sex）の定数と日本語ラベル
    |--------------------------------------------------------------------------
    | 値・ラベルの単一情報源。Blade からは sexLabels() を参照する。
    | AccessLog::actionLabels() と同じ発想で、ビューに条件分岐を書き散らさない。
    */

    public const SEX_MALE = 'male';
    public const SEX_FEMALE = 'female';
    public const SEX_UNKNOWN = 'unknown';

    /**
     * 性別コード → 日本語ラベルのマッピング
     */
    public static function sexLabels(): array
    {
        return [
            self::SEX_MALE => 'オス',
            self::SEX_FEMALE => 'メス',
            self::SEX_UNKNOWN => '不明',
        ];
    }

    protected $fillable = [
        'client_id',
        'name',
        'breed',
        'sex',
        'birth_date',
        'note',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /**
     * 会員
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * 計測値（D-0800）
     *
     * 並び順は計測日時の降順（新しい順）。トレーニー詳細（S-0309）で
     * この並び順のまま表示する。
     */
    public function measurements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TraineeMeasurement::class)
            ->orderBy('measured_date', 'desc')
            ->orderBy('measured_time', 'desc');
    }

    /**
     * 最終更新者（トレーナー）
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'updated_by');
    }

    /**
     * 性別の日本語表示
     */
    public function getSexLabelAttribute(): ?string
    {
        return $this->sex ? (self::sexLabels()[$this->sex] ?? $this->sex) : null;
    }

    /**
     * 誕生日から算出した年齢（年単位・切り捨て）。
     * 誕生日が未登録なら null を返す。
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }
}
