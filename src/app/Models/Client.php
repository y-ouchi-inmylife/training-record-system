<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * クライアントモデル
 */
class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        // 内部ID
        'internal_id',
        // カテゴリー1: 基本情報
        'initial_consultation_date', 'last_name', 'first_name',
        'last_name_kana', 'first_name_kana',
        'birth_date', 'initial_age', 'gender',
        // カテゴリー2: 連絡先
        'phone1', 'phone2', 'phone3', 'email',
        'postal_code', 'address1', 'address2', 'address3', 'address4',
        'nearest_station',
        // カテゴリー7: 支援管理
        'primary_counselor_id', 'cooperating_agencies', 'support_status_id',
        // 最終更新者
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'initial_consultation_date' => 'date',
            'birth_date' => 'date',
            'initial_age' => 'integer',
        ];
    }

    /**
     * 氏名（フルネーム）
     */
    public function getFullNameAttribute(): string
    {
        return $this->last_name . ' ' . $this->first_name;
    }

    /**
     * 氏名かな（フルネーム）
     */
    public function getFullNameKanaAttribute(): string
    {
        $kana = trim(($this->last_name_kana ?? '') . ' ' . ($this->first_name_kana ?? ''));
        return $kana ?: '';
    }

    /**
     * 一覧表示用の氏名
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * 一覧表示用のかな
     */
    public function getDisplayNameKanaAttribute(): string
    {
        return $this->full_name_kana;
    }

    /**
     * 現在の年齢（生年月日から計算、なければ初回時年齢+経過年数で推定）
     */
    public function getEstimatedAgeAttribute(): ?int
    {
        if ($this->birth_date) {
            return $this->birth_date->age;
        }

        if ($this->initial_age !== null && $this->initial_consultation_date) {
            $yearsDiff = $this->initial_consultation_date->diffInYears(now());
            return $this->initial_age + $yearsDiff;
        }

        return null;
    }

    /**
     * 主担当トレーナー
     */
    public function primaryCounselor(): BelongsTo
    {
        return $this->belongsTo(Counselor::class, 'primary_counselor_id');
    }

    /**
     * 支援状態
     */
    public function supportStatus(): BelongsTo
    {
        return $this->belongsTo(SupportStatus::class);
    }

    /**
     * トレーニング記録
     */
    public function counselingRecords(): HasMany
    {
        return $this->hasMany(CounselingRecord::class);
    }

    /**
     * 最終更新者（トレーナー）
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Counselor::class, 'updated_by');
    }
}
