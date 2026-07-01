<?php

namespace App\Mail;

use App\Models\ClientPasswordSetupToken;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * クライアント閲覧解放の招待メール
 *
 * トレーナーが S-0305 で「閲覧を解放する」を行ったとき、対象クライアントに送る。
 * 本文にはパスワード設定URL（トークン付き）と有効期限（72時間）を含める。
 */
class ClientInvitationMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly ClientPasswordSetupToken $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'トレーニング記録閲覧のご案内',
        );
    }

    public function content(): Content
    {
        // 段2 でパスワード設定画面のルート（例: client-portal.password-setup.show）を追加したら、
        // ビュー側の url() 手組みを route() に差し替える。
        return new Content(
            text: 'mail.client-invitation',
            with: [
                'setupUrl' => url('/client/password-setup/' . $this->token->token),
                'expiresAt' => $this->token->expires_at,
            ],
        );
    }
}
