<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * クライアントパスワード再設定トークンモデル（DS-0900、段階 4-4）
 *
 * パスワードを忘れたお客様がログイン画面（S-1401）の「パスワードを忘れた方」から
 * 申し込みを行った際、登録アドレス宛に送る「パスワード再設定リンク」のトークン。
 *
 * リンクは新しいパスワードを設定するためだけの役割で、ログインさせる働きは
 * 持たない・メールアドレスも変えない（ログイン用リンク・メールアドレス確認リンクとは
 * 性質が異なる）。設定完了後はログイン画面へ遷移し、ログイン中だった場合は
 * そのままダッシュボードへ移る（ログアウトはさせない）。
 *
 * 有効期限は config('client_tokens.password_reset_expires_days') 日後で、
 * 他のトークンとは無関係の独立した期限を持つ（引き継ぐ元がないため）。
 */
class ClientPasswordResetToken extends Model
{
    protected $fillable = [
        'token',
        'client_id',
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
