<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\StrongPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * パスワード変更コントローラー
 */
class ChangePasswordController extends Controller
{
    /**
     * パスワード変更画面
     */
    public function show(): View
    {
        return view('auth.change-password');
    }

    /**
     * パスワード変更処理
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'new_password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ], [
            'new_password.required' => '新しいパスワードを入力してください。',
            'new_password.confirmed' => '新しいパスワード（確認）が一致しません。',
        ]);

        $user = auth()->user();

        // パスワードを更新
        $user->update([
            'password' => $request->new_password,
            'must_change_password' => false,
        ]);

        // システム管理者は音声ファイル一覧（S-0701）、それ以外はダッシュボード（S-0101）へ
        $homeRoute = $user->isSystemAdmin() ? 'usage-stats.index' : 'dashboard';

        return redirect()->route($homeRoute)
            ->with('success', 'パスワードを変更しました。');
    }
}
