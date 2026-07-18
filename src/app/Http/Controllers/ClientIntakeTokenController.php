<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientIntakeToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClientIntakeTokenController extends Controller
{
    // 指定クライアントの新しいトークンを発行
    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'expires_in_days' => 'required|integer|in:1,7,14,30',
        ]);

        // 未使用かつ期限内のトークンが残っている場合は再発行不可
        $hasActiveToken = $client->intakeTokens()
            ->where('is_used', false)
            ->where('expires_at', '>=', Carbon::now())
            ->exists();

        if ($hasActiveToken) {
            return back()->with('error', '未使用のURLが残っています');
        }

        // ランダムなトークンを生成（32文字）
        $token = Str::random(32);

        // 有効期限を計算（選択された日数後の23:59:59）
        $expiresAt = Carbon::now()
            ->addDays((int) $validated['expires_in_days'])
            ->endOfDay();

        ClientIntakeToken::create([
            'token' => $token,
            'expires_at' => $expiresAt,
            'is_used' => false,
            'client_id' => $client->id,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('clients.show', $client)
            ->with('success', 'URLを発行しました');
    }

    // 指定クライアントのトークンを削除
    public function destroy(Client $client, $tokenId)
    {
        // 他クライアントのトークンIDが渡された場合を弾くため、client_id で絞る
        $token = $client->intakeTokens()->where('id', $tokenId)->firstOrFail();

        // 使用済みの場合は削除不可
        if ($token->is_used) {
            return redirect()->route('clients.show', $client)
                ->with('error', '使用済みのURLは削除できません');
        }

        $token->delete();

        return redirect()->route('clients.show', $client)
            ->with('success', 'URLを削除しました');
    }
}
