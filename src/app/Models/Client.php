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
        'family_last_name', 'family_first_name',
        'family_last_name_kana', 'family_first_name_kana',
        'family_relationship', 'family_relationship_detail',
        'birth_date', 'initial_age', 'gender',
        // カテゴリー2: 連絡先
        'phone1', 'phone2', 'phone3', 'email',
        'postal_code', 'address1', 'address2', 'address3', 'address4',
        'nearest_station',
        // カテゴリー3: 学歴
        'education_level', 'education_detail', 'education_status', 'education_dropout_expected',
        // カテゴリー4: 職歴
        'employment_type', 'employment_hours', 'employment_period',
        'unemployment_period', 'employment_detail',
        // カテゴリー5: 障害・医療情報
        'disability_physical', 'disability_physical_grade',
        'disability_mental', 'disability_mental_grade',
        'disability_intellectual', 'disability_intellectual_grade',
        'disability_detail', 'hospital', 'medication',
        // カテゴリー6: 生活状況
        'financial_status', 'financial_detail',
        'hikikomori', 'school_refusal', 'bullying',
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
            'education_dropout_expected' => 'boolean',
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
     * 家族氏名（フルネーム）
     */
    public function getFamilyFullNameAttribute(): string
    {
        if (!$this->family_last_name && !$this->family_first_name) {
            return '';
        }
        return trim(($this->family_last_name ?? '') . ' ' . ($this->family_first_name ?? ''));
    }

    /**
     * 家族氏名かな（フルネーム）
     */
    public function getFamilyFullNameKanaAttribute(): string
    {
        $kana = trim(($this->family_last_name_kana ?? '') . ' ' . ($this->family_first_name_kana ?? ''));
        return $kana ?: '';
    }

    /**
     * 一覧表示用の氏名（本人との関係に応じて本人名または家族名を返す）
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->family_relationship === '本人') {
            return $this->full_name;
        }
        return $this->family_full_name ?: $this->full_name;
    }

    /**
     * 一覧表示用のかな（本人との関係に応じて本人かなまたは家族かなを返す）
     */
    public function getDisplayNameKanaAttribute(): string
    {
        if ($this->family_relationship === '本人') {
            return $this->full_name_kana;
        }
        return $this->family_full_name_kana ?: $this->full_name_kana;
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
     * 主担当カウンセラー
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
     * 相談記録
     */
    public function counselingRecords(): HasMany
    {
        return $this->hasMany(CounselingRecord::class);
    }

    /**
     * 最終更新者（カウンセラー）
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Counselor::class, 'updated_by');
    }
}
