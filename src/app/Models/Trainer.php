<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * トレーナーモデル（認証ユーザー）
 */
class Trainer extends Authenticatable
{
    use HasFactory;

    protected $table = 'counselors';

    protected $fillable = [
        'name',
        'login_id',
        'password',
        'role',
        'is_locked',
        'is_active',
        'last_login_at',
        'must_change_password',
        'display_order',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_locked' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * 管理権限を持つか（system_admin または admin）
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['system_admin', 'admin']);
    }

    /**
     * システム管理者か
     */
    public function isSystemAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    /**
     * このトレーナーが「管理者のみ」（admin ロール）かどうか
     *
     * 注意：isAdmin() は admin と system_admin の両方で true を返す（広義 admin）
     * このメソッドは admin ロールのみで true を返す
     */
    public function isAdminOnly(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * このトレーナーが業務担当者（admin または staff）かどうか
     *
     * scopePractitioners() の単体レコード版。system_admin は false を返す。
     */
    public function isPractitioner(): bool
    {
        return in_array($this->role, ['admin', 'staff']);
    }

    /**
     * 実務トレーナー（システム管理を除く）のスコープ
     */
    public function scopePractitioners($query)
    {
        return $query->whereIn('role', ['admin', 'staff']);
    }

    /**
     * 権限の表示名（画面表示用）
     */
    public function getRoleDisplayNameAttribute(): string
    {
        return match ($this->role) {
            'system_admin' => 'システム管理者',
            'admin' => '管理者',
            'staff' => '一般',
            default => '',
        };
    }

    /**
     * 主担当クライアント
     */
    public function primaryClients(): HasMany
    {
        return $this->hasMany(Client::class, 'primary_counselor_id');
    }

    /**
     * 担当したトレーニング記録（担当1）
     */
    public function counselingRecordsAsTrainer1(): HasMany
    {
        return $this->hasMany(TrainingRecord::class, 'counselor1_id');
    }

    /**
     * 担当したトレーニング記録（担当2）
     */
    public function counselingRecordsAsTrainer2(): HasMany
    {
        return $this->hasMany(TrainingRecord::class, 'counselor2_id');
    }

    /**
     * 録音・アップロード・テキスト貼り付けで作成した音声記録
     */
    public function audioRecords(): HasMany
    {
        return $this->hasMany(AudioRecord::class, 'counselor_id');
    }
}
