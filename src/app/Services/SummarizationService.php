<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\SystemSetting;

/**
 * 要約サービス
 *
 * Claude APIを使用して文字起こしテキストを要約する。
 * プロンプトはシステム設定（要約プロンプト設定画面）で管理される。
 *
 */
class SummarizationService
{
    protected Client $client;

    public function __construct()
    {
        $apiKey = config('services.anthropic.api_key');

        if (empty($apiKey)) {
            throw new \Exception('Anthropic API key is not set. Please check your .env file.');
        }

        $this->client = new Client(apiKey: $apiKey);
    }

    /**
     * テキストを要約する
     *
     * @param string $text 文字起こしテキスト
     * @return string 要約テキスト
     */
    public function summarize(string $text): string
    {
        // プロンプトは system_settings に集約（コード側に長文のコピーは持たない）。
        // フォールバックは空文字。DB 未投入時は空プロンプトで Claude に渡る割り切り
        // （本番はシーダー投入済み・リフレッシュ前提のため実運用では発生しない）。
        $promptTemplate = SystemSetting::where('key', 'summary_prompt_current')
            ->value('value') ?? '';

        $response = $this->client->messages->create(
            maxTokens: 2000,
            messages: [
                [
                    'role' => 'user',
                    'content' => $promptTemplate . "\n\n" . $text,
                ],
            ],
            model: 'claude-sonnet-4-20250514',
        );

        return $response->content[0]->text;
    }
}
