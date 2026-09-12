<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPasswordChangeRequest;
use App\Http\Requests\ClientProfileRequest;
use App\Mail\ClientPasswordChangedMail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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

    /**
     * パスワードを変更（PUT /client-portal/settings/password）
     *
     * ログアウトさせない（決定事項 #4）。登録アドレスに通知メールを送る。
     * メール送信失敗時は全ロールバックし、パスワードの更新も無効化する。
     */
    public function updatePassword(ClientPasswordChangeRequest $request): RedirectResponse
    {
        $client = Auth::guard('client')->user();

        DB::transaction(function () use ($client, $request) {
            $client->update([
                'password' => $request->validated()['new_password'],
            ]);
            // 登録アドレスに通知メール。失敗時は全ロールバック
            Mail::to($client->email)->send(new ClientPasswordChangedMail());
        });

        return redirect()
            ->route('client-portal.settings.index')
            ->with('password_success', 'パスワードを変更しました。');
    }
}
