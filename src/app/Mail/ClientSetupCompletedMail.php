<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * クライアント初回設定完了 通知メール
 *
 * 初回設定（パスワード＋基本情報の登録）が完了した直後に、登録アドレス宛に送る通知メール。
 * 本文には次回以降のログインに使えるログインURLと、登録に用いたメールアドレスを載せる。
 * このメールをお客様が保存しておくことで、以降いつでもログイン画面にたどり着ける状態を作る。
 *
 * 設定前に届く `ClientLoginLinkMail`（件名「マイページのご登録手続き」）との役割の違い：
 * - `ClientLoginLinkMail` は**設定用**のリンク（ログイン用リンク＝初回設定画面への導線）を届ける
 *   もので、有効期限があり、1 回使うと使い切りになる
 * - 本メールは**設定完了後**の通知で、載せるのは**恒常的なログインURL**（次回以降ログイン画面の URL）。
 *   有効期限を持たず、お客様の手元に「これを保存しておけばよい」という 1 通を残すのが目的
 * 設計書：client-portal-design-plan.md §6-1 / §6-2、screen-design.md S-1403 備考。
 */
class ClientSetupCompletedMail extends Mailable
{
    use SerializesModels;

    /**
     * @param Client $client 初回設定を完了したクライアント（登録アドレスの取得元）
     * @param string $loginUrl 案内するログインURL（呼び出し側で `url('/')` から組み立てて渡す。
     *                         ホストは現行リクエストに追従するため、`config/subdomain.php` の分岐は不要）
     */
    public function __construct(
        public readonly Client $client,
        public readonly string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return ClientMailEnvelope::build('マイページのご登録が完了しました');
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.client-setup-completed',
            with: [
                'loginUrl' => $this->loginUrl,
                'email' => $this->client->email,
            ],
        );
    }
}
