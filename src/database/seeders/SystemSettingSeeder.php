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
        // ※ このプロンプト本文は暫定（旧・心理カウンセリング用）。
        //    ドッグトレーニング向けへの書き換えは別タスク。
        $currentPrompt = "あなたはカウンセリング・相談業務の記録作成を支援するアシスタントです。\n以下のセッション文字起こしを、後日の記録として参照しやすい形に要約してください。\n\n【要約項目】\n- 相談の主題: クライアントが今回相談したかった主な内容\n- 話された内容の要点: 主な話題とクライアントの発言\n- クライアントの状況・変化: 気づき、課題、進捗、感情面の動き等\n- 合意事項・決定事項: セッション中に決まったこと\n- 次回への引き継ぎ: 次回扱う話題、宿題、継続観察事項等\n\n【出力形式】\n- 各項目は要点を簡潔にまとめる\n- 該当する内容がない項目は「記載なし」と記述\n- 推測で補完せず、文字起こしにある内容のみを要約";

        $settings = [
            ['key' => 'auto_logout_minutes', 'value' => '15'],
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
