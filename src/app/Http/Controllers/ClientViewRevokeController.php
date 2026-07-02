<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPasswordSetupToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * クライアント閲覧解放取り消しコントローラ（トレーナー側の操作）
 *
 * S-0305 クライアント詳細画面から「解放を取り消す」を実行したときの受け口。
 * 解放前の状態に戻す：is_viewable=false、password=null、未使用トークンを全件物理削除、
 * を1トランザクションで実行する。メール送信は行わない（release と対称にしない、
 * 送信済み招待メールは取り消せないため対称性を無理に持たせない）。
 *
 * 既存の release-view との対称性はロジックのみで、コントローラ・ルートは独立に置く
 * （URI・ルート名・コントローラ名すべて revoke で統一）。
 */
class ClientViewRevokeController extends Controller
{
    /**
     * 閲覧解放取り消し処理（POST /clients/{client}/revoke-view）
     */
    public function store(Client $client): RedirectResponse
    {
        DB::transaction(function () use ($client) {
            // is_viewable を false に戻し、設定済みパスワードを破棄する。
            // Client の password は 'hashed' cast だが、cast は null を素通しするため
            // ここで NULL を書き込める（Laravel の castAttributeAsHashedString で確認済）。
            $client->update([
                'is_viewable' => false,
                'password' => null,
            ]);

            // 該当クライアントの未使用招待トークンを全件物理削除。
            // 解放前はレコード不在のため、物理削除が「解放前と同じ状態」に最も近い。
            // 使用済み（is_used=true）は履歴として残す（intake トークンの destroy と同流儀）。
            ClientPasswordSetupToken::where('client_id', $client->id)
                ->where('is_used', false)
                ->delete();
        });

        return redirect()->route('clients.show', $client)
            ->with('success', '閲覧を取り消しました。');
    }
}
