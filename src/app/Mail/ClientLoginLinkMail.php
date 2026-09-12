<?php

namespace App\Mail;

use App\Models\ClientLoginLinkToken;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * クライアントログイン用リンクメール（段階 4-1）
 *
 * クライアントが S-1405 メールアドレス登録画面でメールアドレスを送信したときに、
 * 入力されたアドレスに対して送るメール。本文にはログイン用リンク（＝初回設定画面へ
 * 遷移するリンク。開くだけで client guard でログイン状態になる）と有効期限を含める。
 *
 * リンクの有効期限は、対応するメールアドレス登録用トークンの `expires_at` を
 * そのまま引き継ぐ（お客様がメールアドレスを登録した時点から数え直さない）。
 */
class ClientLoginLinkMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly ClientLoginLinkToken $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'トレーニング記録閲覧のログイン用リンク',
            replyTo: [
                new Address('info@inmylife1965.com', 'インマイライフ'),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.client-login-link',
            with: [
                // ログイン用リンク（初回設定画面のトークン付き URL）。
                'loginUrl' => route('client-portal.setup.show', ['token' => $this->token->token]),
                'expiresAt' => $this->token->expires_at,
            ],
        );
    }
}
