<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * 文字起こしサービス（Whisper API）
 *
 * 将来的にサーバーローカル処理への切り替えを考慮し、
 * API呼び出しをService層で抽象化している。
 *
 * @see docs/architecture.md 4.2 連携時の注意事項
 */
class TranscriptionService
{
    /**
     * 音声ファイルを文字起こしする
     *
     * @param string $filePath Storage上のファイルパス
     * @return array{text: string, duration: float|null}
     * @throws \Exception API呼び出しに失敗した場合
     */
    public function transcribe(string $filePath): array
    {
        $absolutePath = Storage::path($filePath);

        if (!file_exists($absolutePath)) {
            throw new \RuntimeException("音声ファイルが見つかりません: {$filePath}");
        }

        $response = OpenAI::audio()->transcribe([
            'model' => 'whisper-1',
            'file' => fopen($absolutePath, 'r'),
            'language' => 'ja',
            'response_format' => 'verbose_json',
        ]);

        return [
            'text' => $response->text,
            'duration' => $response->duration ?? null,
        ];
    }
}
