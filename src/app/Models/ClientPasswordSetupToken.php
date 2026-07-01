<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * クライアントパスワード設定トークンモデル（DS-0600）
 *
 * 閲覧を解放されたクライアントが招待メールから初回パスワードを設定するための
 * ワンタイムURLのトークン。手本は ClientIntakeToken（DS-0200）。
 * 有効期限は発行から72時間（発行時に expires_at を設定）。
 */
class ClientPasswordSetupToken extends Model
{
    protected $fillable = [
        'token',
        'client_id',
        'expires_at',
        'is_used',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(Trainer::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at < Carbon::now();
    }

    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }
}
