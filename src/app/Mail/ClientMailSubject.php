<?php

namespace App\Mail;

/**
 * お客様側メールの件名を組み立てる共通ヘルパー。
 *
 * 事業者名（`config('app.client_portal_company')`）を先頭に角括弧で付ける形に
 * 統一する（例：「【○○○】マイページのご登録手続き」）。
 * 事業者名が未設定・空の場合は角括弧を出さず、下地の題名だけを返す
 * （例：「マイページのご登録手続き」）。
 *
 * すべての ClientXxxMail クラスの envelope() から呼び出して、件名の形を
 * 一元管理する。事業者名が変わっても各 Mail クラスの書き換えは不要。
 */
class ClientMailSubject
{
    /**
     * @param string $topic 事業者名を除いた件名部分（例：「マイページのご登録手続き」）
     */
    public static function format(string $topic): string
    {
        $company = config('app.client_portal_company');

        if (empty($company)) {
            return $topic;
        }

        return "【{$company}】{$topic}";
    }
}
