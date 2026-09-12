<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * クライアントメールアドレス変更トークンモデル（DS-0800、段階 4-3）
 *
 * ログイン中のクライアントが登録情報設定画面（S-1406）でメールアドレスを
 * 変更する際、新しいアドレス宛に送る「メールアドレス確認リンク」のトークン。
 *
 * 確認までは clients.email を書き換えず、new_email カラムで保持する。
 * リンクが開かれた時点で clients.email に反映し、is_used を true にする。
 * リンク自体は「メールアドレスを切り替えるだけ」の役割で、ログイン用リンク
 * （DS-0600）と違い開いた側でログイン状態にはならない。
 *
 * 有効期限は config('client_tokens.email_change_confirm_expires_days') 日後で、
 * 他のトークンとは無関係の独立した期限を持つ（引き継ぐ元がないため）。
 */
class ClientEmailChangeToken extends Model
{
    protected $fillable = [
        'token',
        'client_id',
        'new_email',
        'expires_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
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
