<?php

namespace App\Http\Controllers;

use App\Mail\ClientInvitationMail;
use App\Models\Client;
use App\Models\ClientPasswordSetupToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * クライアント閲覧解放コントローラ（トレーナー側の操作）
 *
 * S-0305 クライアント詳細画面から「閲覧を解放する」を実行したときの受け口。
 * is_viewable=true 更新・パスワード設定トークン発行・招待メール送信を
 * 1つのトランザクションで行う。メール送信が例外を投げたら全ロールバックし、
 * 中途半端に閲覧解放だけ残る状態を作らない。
 *
 * 名前空間はルート直下（App\Http\Controllers）。Client\ サブディレクトリは
 * クライアント閲覧機能側（auth:client 保護下）の名前空間のため、トレーナー操作である
 * この解放処理はそちらに置かない。
 */
class ClientViewReleaseController extends Controller
{
    /**
     * 閲覧解放処理（POST /clients/{client}/release-view）
     */
    public function store(Client $client): RedirectResponse
    {
        if (empty($client->email)) {
            return back()->with('error', 'メールアドレスが未登録のため解放できません。');
        }

        DB::transaction(function () use ($client) {
            $client->update(['is_viewable' => true]);

            $token = ClientPasswordSetupToken::create([
                'token' => Str::random(32),
                'client_id' => $client->id,
                'expires_at' => now()->addHours(72),
                'is_used' => false,
                'created_by' => Auth::id(),
            ]);

            Mail::to($client->email)->send(new ClientInvitationMail($token));
        });

        return redirect()->route('clients.show', $client)
            ->with('success', '閲覧を解放し、招待メールを送信しました。');
    }
}
