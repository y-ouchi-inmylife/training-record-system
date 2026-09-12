<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientEmailRegistrationToken;
use App\Models\ClientLoginLinkToken;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * メールアドレス登録用 URL の発行コントローラ（トレーナー側の操作）
 *
 * S-0305 クライアント詳細画面から「メールアドレス登録用 URL を発行」を
 * 実行したときの受け口。未発行時の発行と再発行を同じエンドポイントで扱う。
 *
 * 名前空間はルート直下（App\Http\Controllers）。Client\ サブディレクトリは
 * クライアント側（auth:client 保護下）用のため、ここには置かない。
 *
 * 設計書: api-design.md `POST /clients/{client}/email-registration-tokens`
 */
class ClientEmailRegistrationTokenController extends Controller
{
    /**
     * メールアドレス登録用 URL の発行処理
     *
     * 発行後はクライアント詳細画面（S-0305）にリダイレクトし、完了メッセージを
     * 表示する。案内シート（S-0307）を開くのはトレーナー側の判断とし、詳細画面
     * の「登録のご案内を表示」ボタン（別タブで開く）から任意のタイミングで開く。
     *
     * 過去の遷移案の経緯（requirements.md 6-3-6 の備考も参照）：
     * - 案 A：同タブで印刷ページへ遷移 → 印刷ページから戻ったときに詳細画面が
     *   キャッシュで発行前の状態のまま表示される問題があり不採用
     * - 案 B：詳細画面へ戻しつつ `window.open` で別タブを開く → ブラウザの
     *   ポップアップブロックで実用にならず不採用
     * - 現行：詳細画面に戻して完了メッセージだけを表示し、案内シートは
     *   ボタン押下で明示的に開く（当初案に戻した）
     */
    public function store(Client $client): RedirectResponse
    {
        // 既存の未使用トークンがあれば再発行扱い
        $wasReissued = $client->emailRegistrationTokens()
            ->where('is_used', false)
            ->exists();

        DB::transaction(function () use ($client) {
            // 対象クライアントの未使用メールアドレス登録用トークンを物理削除
            $client->emailRegistrationTokens()
                ->where('is_used', false)
                ->delete();

            // 対象クライアントの未使用ログイン用リンクも物理削除
            // 再発行時に送信済みログイン用リンクも無効化するため
            $client->loginLinkTokens()
                ->where('is_used', false)
                ->delete();

            // 新しいトークンを 1 件作成。有効期限は設定値から算出（ハードコード禁止）
            ClientEmailRegistrationToken::create([
                'token' => Str::random(32),
                'client_id' => $client->id,
                'expires_at' => now()->addDays(
                    (int) config('client_tokens.email_registration_expires_days')
                ),
                'is_used' => false,
                'created_by' => Auth::id(),
            ]);
        });

        $message = $wasReissued
            ? 'メールアドレス登録用 URL を発行し直しました。'
            : 'メールアドレス登録用 URL を発行しました。';

        return redirect()
            ->route('clients.show', $client)
            ->with('success', $message);
    }

    /**
     * メールアドレス登録用 URL の印刷用ページ（S-0307）を表示する。
     *
     * 設計書 api-design.md `GET /clients/{client}/email-registration-tokens/print`。
     * お客様の氏名はビューに渡さない（決定事項 #3）。
     */
    public function print(Client $client): View
    {
        $token = $client->emailRegistrationTokens()
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        // 有効なトークンがなければ案内画面を返す（同じビューを状態で切り替え）
        if (! $token) {
            return view('clients.email-registration-token-print', [
                'client' => $client,
                'url' => null,
                'expiresOn' => null,
            ]);
        }

        return view('clients.email-registration-token-print', [
            'client' => $client,
            'url' => route('client-portal.email-registration.show', ['token' => $token->token]),
            'expiresOn' => $token->expires_at,
        ]);
    }
}
