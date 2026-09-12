<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * クライアントメールアドレス変更 完了通知メール（段階 4-3）
 *
 * メールアドレス確認リンクが開かれ clients.email が切り替わった直後に、
 * **古いアドレス**へ送信する通知メール。第三者による変更に本人が気づける
 * ようにする目的（設計書 6-15-10 の本人性の考え方）。
 */
class ClientEmailChangedMail extends Mailable
{
    use SerializesModels;

    public function __construct() {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'メールアドレスが変更されました',
            replyTo: [
                new Address('info@inmylife1965.com', 'インマイライフ'),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.client-email-changed',
        );
    }
}
