<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientInitialSetupRequest;
use App\Models\ClientLoginLinkToken;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * クライアント初回設定コントローラ（S-1403）
 *
 * トークン付き公開 URL（ログイン用リンク）からアクセスする。
 * GET でトークン検証と同時に client guard で自動ログインさせ、
 * POST でパスワード＋基本情報を保存する。
 *
 * ログイン用リンクのトークンは `client_login_link_tokens` テーブル（DS-0600）を使う。
 *
 * 設計書: api-design.md `GET / POST /client-portal/setup/{token}`
 */
class InitialSetupController extends Controller
{
    /**
     * トークンを検証し、初回設定画面を表示（GET /client-portal/setup/{token}）
     * 有効な場合は同時にクライアントを client guard でログインさせる。
     */
    public function showByToken(string $token): View
    {
        $tokenRecord = ClientLoginLinkToken::where('token', $token)->first();

        if (!$tokenRecord) {
            return $this->invalidTokenView(
                'このURLは無効です',
                'URLが間違っているか、削除された可能性があります。担当のトレーナーにお問い合わせください。'
            );
        }
        if ($tokenRecord->isExpired()) {
            return $this->invalidTokenView(
                'このURLは期限切れです',
                'このURLの有効期限が切れています。担当のトレーナーにお問い合わせください。改めて発行してもらいます。'
            );
        }
        if ($tokenRecord->is_used) {
            return $this->invalidTokenView(
                'このURLは既に使用されています',
                'このURLでは既に初回設定が完了しています。ログイン画面からログインしてください。'
            );
        }

        // 自動ログイン。「リンクを開いただけでは使用済みにしない」ため is_used は更新しない。
        Auth::guard('client')->login($tokenRecord->client);
        request()->session()->regenerate();

        return view('client.setup.index', [
            'token' => $token,
            'client' => $tokenRecord->client,
        ]);
    }

    /**
     * 初回設定を保存する（POST /client-portal/setup/{token}）
     */
    public function storeByToken(ClientInitialSetupRequest $request, string $token): View|RedirectResponse
    {
        // トークン再検証（レース対策）。二重開封で後発は「使用済み」エラーになる。
        $tokenRecord = ClientLoginLinkToken::where('token', $token)->first();
        if (!$tokenRecord || $tokenRecord->isExpired() || $tokenRecord->is_used) {
            return $this->invalidTokenView(
                'このURLは既に使用されています',
                'このURLは既に使用されているか、無効になっています。ログイン画面からログインしてください。'
            );
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $tokenRecord) {
            // パスワード・氏名・連絡先を更新（email は S-1405 で登録済みのため触らない）
            $tokenRecord->client->update([
                'password' => $validated['password'],
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'] ?? null,
                'last_name_kana' => $validated['last_name_kana'] ?? null,
                'first_name_kana' => $validated['first_name_kana'] ?? null,
                'phone1' => $validated['phone1'],
                'phone2' => $validated['phone2'] ?? null,
                'postal_code' => $validated['postal_code'],
                'address1' => $validated['address1'],
                'address2' => $validated['address2'],
                'address3' => $validated['address3'],
                'address4' => $validated['address4'] ?? null,
            ]);

            // ログイン用リンクを使い切りにする
            $tokenRecord->update(['is_used' => true]);

            // 対応するメールアドレス登録用トークン（未使用のもの）も使い切りにする。
            // 全体の期限管理はこのタイミングで終了する。
            $tokenRecord->client->emailRegistrationTokens()
                ->where('is_used', false)
                ->update(['is_used' => true]);
        });

        // 自動ログインは GET で完了している。そのままダッシュボードへ。
        return redirect()->route('client-portal.dashboard')
            ->with('success', '初回設定が完了しました。');
    }

    private function invalidTokenView(string $title, string $message): View
    {
        return view('client.setup.invalid-token', [
            'title' => $title,
            'message' => $message,
        ]);
    }
}
