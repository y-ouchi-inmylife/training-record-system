<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\ClientEmailChangedMail;
use App\Models\Client;
use App\Models\ClientEmailChangeToken;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * クライアントメールアドレス変更 確認リンクを開いたときの処理（段階 4-3）
 *
 * 認証不要の公開画面（`auth:client` の外）。リンクを開いた時点で、
 *   1. トークンを検証する
 *   2. 切替の直前に new_email の重複を再確認する
 *   3. clients.email を new_email に更新する
 *   4. トークンを使用済みにする
 *   5. 古いアドレスに変更完了の通知メールを送る
 *   6. ログイン中なら client guard をログアウトさせ、ログイン画面へ遷移する
 * を実行する。ログイン状態を問わず同じ処理を行う（決定事項 #3）。
 *
 * 設計書: api-design.md `GET /client-portal/email-change/{token}`
 */
class EmailChangeController extends Controller
{
    /**
     * 確認リンクを開いたときの処理
     */
    public function confirm(string $token): View|RedirectResponse
    {
        $tokenRecord = ClientEmailChangeToken::where('token', $token)->first();

        if (!$tokenRecord) {
            return $this->invalidTokenView(
                'このURLは無効です',
                'URLが間違っているか、削除された可能性があります。担当のトレーナーにお問い合わせください。'
            );
        }
        if ($tokenRecord->isExpired()) {
            return $this->invalidTokenView(
                'このURLは期限切れです',
                'このURLの有効期限が切れています。登録情報設定画面から改めてお申し込みください。'
            );
        }
        if ($tokenRecord->is_used) {
            return $this->invalidTokenView(
                'このURLは既に使用されています',
                'このURLでは既にメールアドレスの切替が完了しています。ログイン画面からログインしてください。'
            );
        }

        // 切替直前の重複再確認：申し込みから確認までの間に他クライアントが
        // 同じアドレスを登録した可能性があるため、ここでチェックする。
        $duplicated = Client::where('email', $tokenRecord->new_email)
            ->where('id', '!=', $tokenRecord->client_id)
            ->exists();
        if ($duplicated) {
            // トークンは使用済みにしない。clients.email も書き換えない。
            return $this->invalidTokenView(
                'このメールアドレスは登録できません',
                '別のお客様がすでにこのメールアドレスをお使いのため、切替はできませんでした。担当トレーナーにご連絡ください。'
            );
        }

        $client = $tokenRecord->client;
        $oldEmail = $client->email;

        try {
            DB::transaction(function () use ($client, $tokenRecord, $oldEmail) {
                // clients.email を新しいアドレスに切り替える
                $client->update([
                    'email' => $tokenRecord->new_email,
                ]);
                // トークンを使用済みにする
                $tokenRecord->update(['is_used' => true]);
                // 古いアドレスに変更完了の通知メールを送る（本人性の防御）
                if ($oldEmail !== null) {
                    Mail::to($oldEmail)->send(new ClientEmailChangedMail());
                }
            });
        } catch (\Throwable $e) {
            return $this->invalidTokenView(
                'メールアドレスの切替に失敗しました',
                '時間を置いて再度お試しください。改善しない場合は担当のトレーナーにご連絡ください。'
            );
        }

        // ログイン中なら client guard をログアウトさせる（決定事項 #3）。
        // ログイン状態にかかわらず、切替後はクライアントログイン画面へ遷移し、
        // 新しいメールアドレスでログインできることをその場で確かめられるようにする。
        if (Auth::guard('client')->check()) {
            Auth::guard('client')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return redirect()
            ->route('client-portal.login')
            ->with('success', 'メールアドレスを変更しました。新しいメールアドレスでログインしてください。');
    }

    private function invalidTokenView(string $title, string $message): View
    {
        // 既存の無効トークン画面（`client/setup/invalid-token.blade.php`）を流用する。
        // 「pre-auth の説明カード」として汎用に使う想定（設計書 §4-8）。
        return view('client.setup.invalid-token', [
            'title' => $title,
            'message' => $message,
        ]);
    }
}
