<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * クライアントメールアドレス登録トークンモデル（DS-0700）
 *
 * トレーナーが発行する「メールアドレス登録用 URL」のトークン。
 * クライアントはこの URL からメールアドレスを登録し、当該アドレスに
 * ログイン用リンク（DS-0600）が送信される。
 *
 * 有効期限は発行から config('client_tokens.email_registration_expires_days') 日後。
 * この期限が「初回設定完了までの全体の期限」となり、ログイン用リンク（DS-0600）は
 * この期限をそのまま引き継ぐ（お客様の操作で期限は延びない設計）。
 *
 * is_used は「初回設定が完了した時点」で true に更新する。
 * メールアドレス登録単独では使用済みにしない（何度でも入力し直せるようにするため）。
 */
class ClientEmailRegistrationToken extends Model
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
