<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * クライアント登録情報設定コントローラ（S-1406）
 *
 * ログイン中のクライアントが、連絡先・メールアドレス・パスワードを変更する画面。
 * 三つのフォームはそれぞれ独立した送信先を持ち、エラーは名前付きエラーバッグ
 * （profile / email / password）で分離する。
 *
 * 設計書: api-design.md `GET /client-portal/settings`,
 *          `PUT /client-portal/settings/profile`,
 *          `POST /client-portal/settings/email-change`,
 *          `PUT /client-portal/settings/password`
 */
class SettingsController extends Controller
{
    /**
     * 登録情報設定画面を表示（GET /client-portal/settings）
     */
    public function index(): View
    {
        return view('client.settings.index', [
            'client' => Auth::guard('client')->user(),
        ]);
    }

    /**
     * 基本情報（連絡先）を更新（PUT /client-portal/settings/profile）
     *
     * 氏名・メールアドレス・パスワードには一切触れない。
     */
    public function updateProfile(ClientProfileRequest $request): RedirectResponse
    {
        $client = Auth::guard('client')->user();
        $client->update($request->validated());

        return redirect()
            ->route('client-portal.settings.index')
            ->with('profile_success', '基本情報を保存しました。');
    }
}
