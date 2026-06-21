<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 自動ログアウト設定コントローラー
 */
class AutoLogoutController extends Controller
{
    /**
     * 自動ログアウト設定画面
     */
    public function edit(): View
    {
        $settings = SystemSetting::pluck('value', 'key');
        return view('settings.auto-logout', compact('settings'));
    }

    /**
     * 自動ログアウト設定の更新
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'auto_logout_minutes' => 'required|integer|in:0,3,5,10,15,30,60',
        ], [
            'auto_logout_minutes.required' => '自動ログアウト時間は必須です。',
            'auto_logout_minutes.integer' => '自動ログアウト時間は整数で入力してください。',
            'auto_logout_minutes.in' => '自動ログアウト時間は選択肢から選んでください。',
        ]);

        SystemSetting::updateOrCreate(['key' => 'auto_logout_minutes'], ['value' => $validated['auto_logout_minutes']]);

        return redirect()->route('settings.auto-logout.edit')
            ->with('success', '自動ログアウト設定を更新しました。');
    }
}
