<?php

namespace Tests\Feature;

use App\Mail\ClientInvitationMail;
use App\Models\ClientPasswordSetupToken;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * クライアント招待メールの送信検証テスト（塊D 段1）
 *
 * 塊A の疎通確認治具（MailConnectivityTest）を本物の招待メールテストに置き換えた。
 * Mail::fake() 経由で ClientInvitationMail が指定宛先に送信対象として登録されることを検証する。
 *
 * 送信手段（log ドライバ）や本文の細部はここでは検証しない。
 * 閲覧解放処理（ClientViewReleaseController）の DB を伴う結合検証は、本プロジェクトに
 * factory が無く RefreshDatabase もマイグレーションの MySQL 固有 CHECK 制約で使えない
 * ため、段1では最小構成に留める（実装1/2 の probe で動作は確認済み）。
 */
class ClientInvitationMailTest extends TestCase
{
    /** Mail::to()->send() で ClientInvitationMail が指定宛先に送信対象として登録される */
    public function test_招待メールが指定宛先に送信される(): void
    {
        Mail::fake();

        $token = new ClientPasswordSetupToken([
            'token' => 'test-token-abcdef',
            'client_id' => 1,
            'expires_at' => now()->addHours(72),
            'is_used' => false,
        ]);

        Mail::to('invite-test@example.com')->send(new ClientInvitationMail($token));

        Mail::assertSent(
            ClientInvitationMail::class,
            fn (ClientInvitationMail $mail) => $mail->hasTo('invite-test@example.com'),
        );
    }

    /** ClientInvitationMail はコンストラクタで受けたトークンを保持する */
    public function test_招待メールはコンストラクタで受けたトークンを保持する(): void
    {
        $token = new ClientPasswordSetupToken([
            'token' => 'holder-check-token',
            'client_id' => 42,
            'expires_at' => now()->addHours(72),
            'is_used' => false,
        ]);

        $mail = new ClientInvitationMail($token);

        $this->assertSame('holder-check-token', $mail->token->token);
        $this->assertSame(42, $mail->token->client_id);
    }
}
