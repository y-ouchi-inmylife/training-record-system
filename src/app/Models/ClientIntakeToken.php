<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ClientIntakeToken extends Model
{
    protected $fillable = [
        'token',
        'expires_at',
        'is_used',
        'client_id',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    // クライアントとのリレーション
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // 発行者（トークンを発行したトレーナー）とのリレーション
    public function creator()
    {
        return $this->belongsTo(Trainer::class, 'created_by');
    }

    // 有効期限切れかチェック
    public function isExpired(): bool
    {
        return $this->expires_at < Carbon::now();
    }

    // 有効なトークンか（未使用 & 期限内）
    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }

    // 状態を取得（未使用/使用済み/期限切れ）
    public function getStatusAttribute(): string
    {
        if ($this->is_used) {
            return '使用済み';
        }
        if ($this->isExpired()) {
            return '期限切れ';
        }
        return '未使用';
    }

    // 状態バッジのクラス名
    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->is_used) {
            return 'bg-secondary';
        }
        if ($this->isExpired()) {
            return 'bg-danger';
        }
        return 'bg-success';
    }

    /**
     * 残り日数（切り上げ）
     *
     * 「あと何日使えるか」を伝えるための表示用。
     * 切り捨てだと期限当日に「残り0日」となり必要以上に急かすため、
     * 切り上げで返す。期限切れ（expires_at < now）の場合は 0 を返す。
     */
    public function getRemainingDaysAttribute(): int
    {
        $hoursLeft = now()->diffInHours($this->expires_at, absolute: false);

        return max(0, (int) ceil($hoursLeft / 24));
    }
}
