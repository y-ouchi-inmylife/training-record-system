<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * システム設定の初期データ
 */
class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        // 要約プロンプトの正本テキストはこのシーダーに集約する（DEFAULT_PROMPT 等のコード側コピーは持たない）。
        // ※ このプロンプト本文はドッグトレーニング向けの初期案。
        //    ドッグトレーナーへの聞き取り後に要調整。
        $currentPrompt = "あなたはドッグトレーニング・しつけ相談の記録作成を支援するアシスタントです。\n飼い主が愛犬の問題行動やしつけの悩みについてドッグトレーナーに相談した、その聞き取りの文字起こしを、後日の記録として参照しやすい形に要約してください。\n\n【要約項目】\n- 相談の主訴: 飼い主が今回いちばん困っている、相談したかった犬の問題行動やしつけの悩み\n- 犬の基本情報: 会話で語られた犬種・年齢・性別・避妊去勢の有無・飼育歴など（語られた範囲で）\n- 問題行動の状況: いつ・どこで・どんな時に起きるか、頻度や程度、きっかけ(トリガー)となる状況\n- 家庭環境・生活状況: 家族構成、住環境、運動・散歩・食事・睡眠などの日常、これまでのしつけ方や飼い主の対応\n- トレーナーの見立て・アドバイス: トレーナーが示した原因の見立て、提案したトレーニング方針や具体的な方法\n- 合意した方針・次回までの宿題: 今回決まったこと、飼い主が家庭で取り組むこと、観察してほしいこと\n\n【出力形式】\n- 各項目は要点を簡潔にまとめる\n- 該当する内容がない項目は「記載なし」と記述\n- 推測で補完せず、文字起こしにある内容のみを要約";

        $settings = [
            ['key' => 'enable_ip_restriction', 'value' => '0'],
            ['key' => 'summary_prompt_current', 'value' => $currentPrompt],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
