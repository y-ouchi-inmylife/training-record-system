<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * クライアントのメールアドレス削除コントローラ（段階 4-2）
 *
 * S-0305 クライアント詳細画面から「メールアドレスを削除」を実行したときの受け口。
 * 「利用中」のクライアントに対してのみ実行可能。メールアドレスとパスワードを NULL に
 * 戻し、未使用のメールアドレス登録用 URL・ログイン用リンクを物理削除する。
 * クライアント情報とトレーニング記録は保持する（設計書 6-3-7）。
 *
 * 設計書: api-design.md `DELETE /clients/{client}/email`
 */
class ClientEmailController extends Controller
{
    /**
     * メールアドレス削除処理（DELETE /clients/{client}/email）
     */
    public function destroy(Client $client): RedirectResponse
    {
        // 利用中（email も password も非 NULL）でなければ実行しない。
        // UI 側で利用中のときだけボタンを出しているため通常は到達しないが、
        // 直接呼ばれた場合の防御として 409 を返す。
        if ($client->email === null || $client->password === null) {
            abort(409, '利用中のときだけメールアドレスを削除できます');
        }

        DB::transaction(function () use ($client) {
            // password は 'hashed' cast だが、cast は null を素通しするため NULL を書ける
            $client->update([
                'email' => null,
                'password' => null,
            ]);

            // 未使用の登録用 URL・ログイン用リンクを物理削除
            $client->emailRegistrationTokens()
                ->where('is_used', false)
                ->delete();
            $client->loginLinkTokens()
                ->where('is_used', false)
                ->delete();
        });

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'メールアドレスを削除しました。');
    }
}
