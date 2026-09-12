<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * クライアントパスワード変更 完了通知メール（段階 4-3）
 *
 * ログイン中のクライアントが登録情報設定画面でパスワードを変更したときに、
 * 登録アドレスに送る通知メール。心当たりがない場合は担当トレーナーに
 * 連絡するよう案内する。設計書: 6-15-11 パスワードの変更。
 */
class ClientPasswordChangedMail extends Mailable
{
    use SerializesModels;

    public function __construct() {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'パスワードが変更されました',
            replyTo: [
                new Address('info@inmylife1965.com', 'インマイライフ'),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.client-password-changed',
        );
    }
}
