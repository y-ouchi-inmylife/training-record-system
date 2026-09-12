<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPasswordResetLinkRequest;
use App\Mail\ClientPasswordResetMail;
use App\Models\Client;
use App\Models\ClientPasswordResetToken;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * クライアントパスワード再設定コントローラ（段階 4-4）
 *
 * 認証不要の公開画面（`auth:client` の外）。
 *  - 申し込み画面（S-1407）：メールアドレスを入力して再設定リンクを申し込む
 *  - 再設定画面（S-1408）：届いたリンクから新しいパスワードを設定する
 *
 * 設計書:
 *   - api-design.md `GET / POST /client-portal/password-reset`
 *   - api-design.md `GET / POST /client-portal/password-reset/{token}`
 */
class PasswordResetController extends Controller
{
    /**
     * 申し込み画面を表示（GET /client-portal/password-reset）
     */
    public function showRequestForm(): View
    {
        return view('client.password-reset.request');
    }

    /**
     * 申し込みを受け付ける（POST /client-portal/password-reset）
     *
     * 該当する「利用中」のお客様がいる場合のみ、パスワード再設定リンクを送信する。
     * 該当しない場合（未登録・利用中でない）も**同じ画面・同じ文言を返す**
     * （第三者に登録の有無を露呈させないため。設計書 6-15-12）。
     */
    public function sendResetLink(ClientPasswordResetLinkRequest $request): View
    {
        $email = $request->validated()['email'];

        $client = Client::where('email', $email)->first();

        // 該当し、状態が「利用中」の場合のみ発行・送信する。
        // 状態判定は段階 4-2 で Client モデルに定義したものを使う（判定を書き直さない）。
        if ($client) {
            $client->loadStatusData();
            if ($client->status === Client::STATUS_IN_USE) {
                DB::transaction(function () use ($client) {
                    // 同時に有効な再設定リンクは 1 本のみ（決定事項 #4）：
                    // 未使用のトークンを物理削除してから新規発行する。
                    $client->passwordResetTokens()
                        ->where('is_used', false)
                        ->delete();

                    $token = ClientPasswordResetToken::create([
                        'token' => Str::random(32),
                        'client_id' => $client->id,
                        'expires_at' => now()->addDays(
                            (int) config('client_tokens.password_reset_expires_days')
                        ),
                        'is_used' => false,
                    ]);

                    // 登録アドレス宛に再設定リンクを送信。失敗時は全ロールバック
                    Mail::to($client->email)->send(new ClientPasswordResetMail($token));
                });
            }
        }

        // 該当しても・しなくても、同じ完了状態を返す
        return view('client.password-reset.request', [
            'submitted' => true,
        ]);
    }
}
