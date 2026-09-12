<?php

namespace App\Mail;

use App\Models\ClientPasswordResetToken;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * クライアントパスワード再設定リンクメール（段階 4-4）
 *
 * ログイン画面から「パスワードを忘れた方」経由で申し込みを受けたとき、
 * 該当する利用中のお客様の登録アドレスへ送るリンク付きメール。
 * 本文にはパスワード再設定リンクと有効期限を含める。
 *
 * このリンクは新しいパスワードを設定するためだけの役割で、ログインさせる
 * 働きは持たない（ログイン用リンクとは性質が異なる）。
 */
class ClientPasswordResetMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly ClientPasswordResetToken $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'パスワード再設定のご案内',
            replyTo: [
                new Address('info@inmylife1965.com', 'インマイライフ'),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.client-password-reset',
            with: [
                // トークン付きの再設定 URL。ルート名 `client-portal.password-reset.reset.show`
                // は段階 4-4 コミット 2 で登録されるが、URL 構造は設計書で確定済み
                // （/client-portal/password-reset/{token}）のため、url() で組み立てる。
                'resetUrl' => url('/client-portal/password-reset/' . $this->token->token),
                'expiresAt' => $this->token->expires_at,
            ],
        );
    }
}
