<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 要約プロンプト設定コントローラー
 */
class SummaryPromptController extends Controller
{
    /**
     * 要約プロンプト設定画面
     */
    public function edit(): View
    {
        // プロンプト系の値はすべて system_settings に集約（コード側に長文のコピーは持たない）。
        // フォールバックは空文字（完全 DB 依存）。本番はシーダー投入済み・リフレッシュ前提のため空にはならない。
        $currentPrompt = SystemSetting::where('key', 'summary_prompt_current')
            ->value('value') ?? '';

        return view('settings.summary-prompts', compact('currentPrompt'));
    }

    /**
     * 要約プロンプト設定更新
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_prompt' => 'required|string|max:5000',
        ], [
            'current_prompt.required' => '要約プロンプトを入力してください。',
            'current_prompt.max' => '要約プロンプトは5000文字以内で入力してください。',
        ]);

        SystemSetting::updateOrCreate(
            ['key' => 'summary_prompt_current'],
            ['value' => $validated['current_prompt']]
        );

        return redirect()->route('settings.summary-prompts.edit')
            ->with('success', '要約プロンプト設定を保存しました。');
    }
}
