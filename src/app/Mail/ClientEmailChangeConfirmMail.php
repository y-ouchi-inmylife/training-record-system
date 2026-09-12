<?php

namespace App\Mail;

use App\Models\ClientEmailChangeToken;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * クライアントメールアドレス変更 確認リンクメール（段階 4-3）
 *
 * ログイン中のクライアントがメールアドレスを変更する申し込みを行ったとき、
 * 新しいアドレス宛に送信する。本文にはメールアドレス確認リンク（トークン付き）と
 * 有効期限を含める。リンクを開くと clients.email が切り替わり、続けて
 * ログアウトして S-1401 クライアントログイン画面へ遷移する。
 *
 * 「メールアドレス確認リンク」はメールアドレスを切り替えるだけの役割で、
 * ログイン用リンク（DS-0600）とは性質が異なる（ログインさせる働きは持たない）。
 */
class ClientEmailChangeConfirmMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly ClientEmailChangeToken $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'メールアドレス変更のご確認',
            replyTo: [
                new Address('info@inmylife1965.com', 'インマイライフ'),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.client-email-change-confirm',
            with: [
                'confirmUrl' => route('client-portal.email-change.confirm', ['token' => $this->token->token]),
                'expiresAt' => $this->token->expires_at,
            ],
        );
    }
}
