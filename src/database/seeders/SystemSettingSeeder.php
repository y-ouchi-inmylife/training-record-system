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
        // カウンセリング用は「現在適用中の初期値(summary_prompt_current)」と
        // 「プリセット原本(summary_prompt_preset_counseling)」で同じ文面を使うが、
        // 役割の異なる2つの設定値であり、定義は下記の $counselingPrompt 変数1箇所に集約している（二重管理ではない）。
        $counselingPrompt = "あなたはカウンセリング・相談業務の記録作成を支援するアシスタントです。\n以下のセッション文字起こしを、後日の記録として参照しやすい形に要約してください。\n\n【要約項目】\n- 相談の主題: クライアントが今回相談したかった主な内容\n- 話された内容の要点: 主な話題とクライアントの発言\n- クライアントの状況・変化: 気づき、課題、進捗、感情面の動き等\n- 合意事項・決定事項: セッション中に決まったこと\n- 次回への引き継ぎ: 次回扱う話題、宿題、継続観察事項等\n\n【出力形式】\n- 各項目は要点を簡潔にまとめる\n- 該当する内容がない項目は「記載なし」と記述\n- 推測で補完せず、文字起こしにある内容のみを要約";

        $employmentPrompt = "あなたは就労支援業務の記録作成を支援するアシスタントです。\n以下のセッション文字起こしを、後日の記録として参照しやすい形に要約してください。\n面談には利用者本人のほか、家族や支援機関の担当者等が同席する場合があります。\n発言を要約する際は、誰の発言・誰についての情報かが分かるように記述してください（本人・家族・相談員など発言者を明示する）。\n\n【要約項目】\n- 相談の主題: 今回のセッションで扱った主な支援テーマ\n- 話された内容の要点: 主な話題と、誰の発言かが分かるようにまとめた発言内容\n- 利用者の状況・変化: 就労状況、生活面、体調、意欲等の変化・課題（家族など本人以外から語られた情報は、その旨が分かるように記述する）\n- 合意事項・決定事項: セッション中に決まったこと（次回までの行動目標等）\n- 次回への引き継ぎ: 次回扱う話題、確認事項、同行支援の予定等\n\n【出力形式】\n- 各項目は要点を簡潔にまとめる\n- 該当する内容がない項目は「記載なし」と記述\n- 推測で補完せず、文字起こしにある内容のみを要約";

        $settings = [
            ['key' => 'auto_logout_minutes', 'value' => '15'],
            ['key' => 'enable_ip_restriction', 'value' => '0'],
            ['key' => 'summary_prompt_current', 'value' => $counselingPrompt],
            ['key' => 'summary_prompt_preset_counseling', 'value' => $counselingPrompt],
            ['key' => 'summary_prompt_preset_employment', 'value' => $employmentPrompt],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
