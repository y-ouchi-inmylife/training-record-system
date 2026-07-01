<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * メール送信疎通確認用の治具 Mailable
 *
 * 塊A（メール送信の土台）の疎通確認のみが目的で、塊D（クライアント招待メール）で削除する。
 * 本番の招待メールとの混同を避けるため、名前は本番想定名を使わない。
 */
class ConnectivityTestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'メール送信疎通確認',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.connectivity-test',
        );
    }
}
