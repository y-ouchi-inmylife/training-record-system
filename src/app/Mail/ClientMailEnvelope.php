<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

/**
 * お客様側メールの Envelope（件名・差出人・返信先）を組み立てる共通ヘルパー。
 *
 * 全 5 通の ClientXxxMail クラスの `envelope()` からは `build($topic)` を 1 回
 * 呼ぶだけで、下記の 3 要素をまとめて設定できる。件名の書式・差出人名・返信先の
 * 決まり方を 1 か所に集約し、事業者名や返信先アドレスが変わったときの反映を
 * 各 Mail クラスに広げない。
 *
 * - subject: 「【事業者名】$topic」（`client_portal_company` が空なら $topic のみ）
 * - from:    差出人アドレスは `MAIL_FROM_ADDRESS` を使う（値そのものは変えない）。
 *            表示名は事業者名（`client_portal_company`）。空のときは表示名を付けず、
 *            アドレスのみを From に載せる（管理システム名「トレーニング記録管理システム」を
 *            お客様に見せたくないため、`MAIL_FROM_NAME` のグローバル既定にはフォール
 *            バックさせない）
 * - replyTo: `client_portal_reply_to` が設定されていればそのアドレスを 1 件だけ載せる。
 *            空のときは Reply-To ヘッダを付けない（返信は From アドレスに届く）。
 *            従前は開発会社アドレスがハードコードされていたが、お客様の返信は
 *            事業者に届くべきで、アドレスが確定するまでは指定しない方針
 *
 * 設計書：client-portal-design-plan.md §6-1「お客様に送るメールの件名」参照
 */
class ClientMailEnvelope
{
    public static function build(string $topic): Envelope
    {
        return new Envelope(
            subject: self::formatSubject($topic),
            from: self::from(),
            replyTo: self::replyTo(),
        );
    }

    private static function formatSubject(string $topic): string
    {
        $company = config('app.client_portal_company');

        if (empty($company)) {
            return $topic;
        }

        return "【{$company}】{$topic}";
    }

    private static function from(): Address
    {
        $address = config('mail.from.address');
        $company = config('app.client_portal_company');

        // 事業者名が空のときは表示名を付けない（アドレスのみ）
        return empty($company)
            ? new Address($address)
            : new Address($address, $company);
    }

    /**
     * @return array<int, Address>
     */
    private static function replyTo(): array
    {
        $address = config('app.client_portal_reply_to');

        if (empty($address)) {
            return [];
        }

        return [new Address($address)];
    }
}
