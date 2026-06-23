<?php

namespace App\Http\Controllers;

use App\Rules\StrongPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * マイプロフィール管理コントローラー
 */
class ProfileController extends Controller
{
    /**
     * プロフィール編集画面を表示
     */
    public function edit(): View
    {
        return view('profile.edit', [
            'trainer' => Auth::user(),
        ]);
    }

    /**
     * プロフィール情報を更新
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ], [
            'name.required' => '名前を入力してください。',
            'name.max' => '名前は100文字以内で入力してください。',
        ]);

        Auth::user()->update($validated);

        return redirect()->route('profile.edit')
            ->with('success', 'プロフィールを更新しました。');
    }

    /**
     * 自分のパスワード変更画面を表示。
     */
    public function editPassword(): View
    {
        return view('profile.password');
    }

    /**
     * パスワードを変更
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ], [
            'current_password.required' => '現在のパスワードを入力してください。',
            'new_password.required' => '新しいパスワードを入力してください。',
            'new_password.confirmed' => '新しいパスワードと一致しません。',
        ]);

        // 現在のパスワードを照合
        if (!Hash::check($validated['current_password'], Auth::user()->password)) {
            return redirect()->route('profile.password.edit')
                ->withErrors(['current_password' => '現在のパスワードが正しくありません。'])
                ->withInput();
        }

        Auth::user()->update([
            'password' => $validated['new_password'],
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'パスワードを変更しました。');
    }
}
