<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * クライアントメールアドレス変更 完了通知メール（段階 4-3）
 *
 * メールアドレス確認リンクが開かれ clients.email が切り替わった直後に、
 * **古いアドレス**へ送信する通知メール。第三者による変更に本人が気づける
 * ようにする目的（設計書 6-15-10 の本人性の考え方）。**変更が行われた日時**
 * を本文に載せて、身に覚えがあるかをお客様が判断できるようにする。
 * 設計書: 6-15-10 メールアドレスの変更 / client-portal-design-plan.md §6-2。
 */
class ClientEmailChangedMail extends Mailable
{
    use SerializesModels;

    /**
     * @param CarbonInterface $changedAt メールアドレスの切替が行われた日時（呼び出し側で now() を取得して渡す）
     */
    public function __construct(
        public readonly CarbonInterface $changedAt,
    ) {}

    public function envelope(): Envelope
    {
        return ClientMailEnvelope::build('メールアドレスを変更しました');
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.client-email-changed',
            with: [
                'changedAt' => $this->changedAt,
            ],
        );
    }
}
