<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * クライアントパスワード変更 完了通知メール（段階 4-3）
 *
 * ログイン中のクライアントが登録情報設定画面でパスワードを変更したときに、
 * 登録アドレスに送る通知メール。**変更が行われた日時**を本文に載せて、
 * 身に覚えがあるかをお客様が判断できるようにする（決定事項：本人性の確認補助）。
 * 心当たりがない場合は担当トレーナーに連絡するよう案内する。
 * 設計書: 6-15-11 パスワードの変更 / client-portal-design-plan.md §6-2。
 */
class ClientPasswordChangedMail extends Mailable
{
    use SerializesModels;

    /**
     * @param CarbonInterface $changedAt パスワード変更が行われた日時（呼び出し側で now() を取得して渡す）
     */
    public function __construct(
        public readonly CarbonInterface $changedAt,
    ) {}

    public function envelope(): Envelope
    {
        return ClientMailEnvelope::build('パスワードを変更しました');
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.client-password-changed',
            with: [
                'changedAt' => $this->changedAt,
            ],
        );
    }
}
