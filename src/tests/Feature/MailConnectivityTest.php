<?php

namespace Tests\Feature;

use App\Mail\ConnectivityTestMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * メール送信の疎通確認テスト（塊A）
 *
 * 本プロジェクト初のメール検証テスト。Mail::fake() 経由で送信意図が発火することだけを検証する。
 * 送信手段（log ドライバ）や本文（View）そのものはここでは検証しない。
 *
 * ConnectivityTestMail は塊D（本番のクライアント招待メール）で削除する治具のため、
 * このテストも同時に整理する前提。
 */
class MailConnectivityTest extends TestCase
{
    /** Mail::to()->send() で ConnectivityTestMail が送信対象として登録される */
    public function test_疎通確認メールが送信される(): void
    {
        Mail::fake();

        Mail::to('test@example.com')->send(new ConnectivityTestMail());

        Mail::assertSent(
            ConnectivityTestMail::class,
            fn (ConnectivityTestMail $mail) => $mail->hasTo('test@example.com'),
        );
    }
}
