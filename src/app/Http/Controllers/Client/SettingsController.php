<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientEmailChangeRequest;
use App\Http\Requests\ClientPasswordChangeRequest;
use App\Http\Requests\ClientProfileRequest;
use App\Mail\ClientEmailChangeConfirmMail;
use App\Mail\ClientPasswordChangedMail;
use App\Models\ClientEmailChangeToken;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * クライアント登録情報設定コントローラ（S-1406 / S-1409 / S-1410）
 *
 * ログイン中のクライアントが、連絡先・メールアドレス・パスワードを変更する。
 * 三つの機能を三画面に分けている（それぞれフォームが一つだけになるため、
 * エラーメッセージ・完了メッセージが画面上部に自然に載る）。
 * 完了メッセージは共通キー 'success' で画面上部に表示する。
 *
 * 設計書: api-design.md `GET/PUT /client-portal/settings`,
 *          `GET/POST /client-portal/settings/email`,
 *          `GET/PUT /client-portal/settings/password`
 */
class SettingsController extends Controller
{
    /**
     * 登録情報画面を表示（GET /client-portal/settings、S-1406）
     *
     * 基本情報の変更フォームと、メール変更（S-1409）・パスワード変更（S-1410）
     * への入口カードを配置する。
     */
    public function index(): View
    {
        return view('client.settings.index', [
            'client' => Auth::guard('client')->user(),
        ]);
    }

    /**
     * メールアドレス変更画面を表示（GET /client-portal/settings/email、S-1409）
     */
    public function editEmail(): View
    {
        return view('client.settings.email', [
            'client' => Auth::guard('client')->user(),
        ]);
    }

    /**
     * パスワード変更画面を表示（GET /client-portal/settings/password、S-1410）
     */
    public function editPassword(): View
    {
        return view('client.settings.password', [
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
            ->with('success', '基本情報を保存しました。');
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
        // 変更が行われた時刻。通知メールに載せて、身に覚えがあるかをお客様が
        // 判断できるようにする（設計書 client-portal-design-plan.md §6-2）
        $changedAt = now();

        DB::transaction(function () use ($client, $request, $changedAt) {
            $client->update([
                'password' => $request->validated()['new_password'],
            ]);
            // 登録アドレスに通知メール。失敗時は全ロールバック
            Mail::to($client->email)->send(new ClientPasswordChangedMail($changedAt));
        });

        return redirect()
            ->route('client-portal.settings.index')
            ->with('success', 'パスワードを変更しました。');
    }

    /**
     * メールアドレス変更の申し込み（POST /client-portal/settings/email）
     *
     * 新しいアドレス宛にメールアドレス確認リンクを送信する。
     * この時点では clients.email を書き換えない（決定事項 6-15-10）。
     * 同時に有効な確認リンクは 1 本のみ（決定事項 #6）：未使用の既存トークンを物理削除する。
     */
    public function requestEmailChange(ClientEmailChangeRequest $request): RedirectResponse
    {
        $client = Auth::guard('client')->user();
        $newEmail = $request->validated()['new_email'];

        DB::transaction(function () use ($client, $newEmail) {
            // それまで送信済みだった未使用の確認リンクを無効化（物理削除）
            $client->emailChangeTokens()
                ->where('is_used', false)
                ->delete();

            // 新しい確認リンクを 1 件作成。期限は独立（他トークンとは無関係）
            $token = ClientEmailChangeToken::create([
                'token' => Str::random(32),
                'client_id' => $client->id,
                'new_email' => $newEmail,
                'expires_at' => now()->addDays(
                    (int) config('client_tokens.email_change_confirm_expires_days')
                ),
                'is_used' => false,
            ]);

            // 新しいメールアドレス宛に確認リンクを送る。失敗時は全ロールバック
            Mail::to($newEmail)->send(new ClientEmailChangeConfirmMail($token));
        });

        return redirect()
            ->route('client-portal.settings.index')
            ->with('success', '新しいメールアドレス宛に確認メールを送信しました。メールのリンクを開くとメールアドレスが切り替わります。');
    }
}
