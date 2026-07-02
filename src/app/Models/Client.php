<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * クライアントモデル
 *
 * 柱2（クライアント閲覧機能）向けに Authenticatable を継承。
 * トレーナー用の web guard とは別 guard で認証する前提のため、
 * config/auth.php での guard/provider 定義は塊C で行う。
 */
class Client extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        // 内部ID
        'internal_id',
        // カテゴリー1: 基本情報
        'initial_consultation_date', 'last_name', 'first_name',
        'last_name_kana', 'first_name_kana',
        'birth_date', 'gender',
        // カテゴリー2: 連絡先
        'phone1', 'phone2', 'email',
        'postal_code', 'address1', 'address2', 'address3', 'address4',
        // クライアント閲覧機能（柱2）
        'password', 'is_viewable',
        // カテゴリー7: 支援管理
        'primary_trainer_id', 'support_status_id',
        // 最終更新者
        'updated_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'initial_consultation_date' => 'date',
            'birth_date' => 'date',
            'password' => 'hashed',
            'is_viewable' => 'boolean',
        ];
    }

    /**
     * email の空文字を NULL に正規化する。
     *
     * clients.email には UNIQUE 制約があり、MySQL は NULL を重複扱いしないが
     * '' は普通の値として重複扱いする。フォーム未入力を '' で保存すると2件目で
     * UNIQUE 違反になるため、モデル層で '' → NULL に統一する。
     */
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = ($value === '' || $value === null) ? null : $value;
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
     * 現在の年齢（生年月日から計算。生年月日が無ければ不明）
     */
    public function getEstimatedAgeAttribute(): ?int
    {
        if ($this->birth_date) {
            return $this->birth_date->age;
        }

        return null;
    }

    /**
     * 主担当トレーナー
     */
    public function primaryTrainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'primary_trainer_id');
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
    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * 最終更新者（トレーナー）
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'updated_by');
    }
}
