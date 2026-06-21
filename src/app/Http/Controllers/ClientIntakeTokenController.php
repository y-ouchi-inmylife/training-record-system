<?php

namespace App\Http\Controllers;

use App\Models\ClientIntakeToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClientIntakeTokenController extends Controller
{
    // URL管理画面を表示
    public function index()
    {
        $tokens = ClientIntakeToken::with(['client', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return view('client-intake-tokens.index', compact('tokens'));
    }

    // 新しいトークンを発行
    public function store(Request $request)
    {
        $validated = $request->validate([
            'initial_consultation_date' => 'required|date',
            'email' => 'nullable|email|max:255',
            'expires_in_days' => 'required|integer|in:1,7,14,30',
            'memo' => 'nullable|string|max:500',
        ]);

        // ランダムなトークンを生成（32文字）
        $token = Str::random(32);

        // 有効期限を計算（選択された日数後の23:59:59）
        $expiresAt = Carbon::now()
            ->addDays((int) $validated['expires_in_days'])
            ->endOfDay();

        ClientIntakeToken::create([
            'token' => $token,
            'initial_consultation_date' => $validated['initial_consultation_date'],
            'email' => $validated['email'] ?? null,
            'memo' => $validated['memo'] ?? null,
            'expires_at' => $expiresAt,
            'is_used' => false,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('client-intake-tokens.index')
            ->with('success', 'URLを発行しました');
    }

    // トークンを削除
    public function destroy($id)
    {
        $token = ClientIntakeToken::findOrFail($id);

        // 使用済みの場合は削除不可
        if ($token->is_used) {
            return redirect()->route('client-intake-tokens.index')
                ->with('error', '使用済みのURLは削除できません');
        }

        $token->delete();

        return redirect()->route('client-intake-tokens.index')
            ->with('success', 'URLを削除しました');
    }
}
