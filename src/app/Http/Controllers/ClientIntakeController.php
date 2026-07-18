<?php

namespace App\Http\Controllers;

use App\Models\ClientIntakeToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientIntakeController extends Controller
{
    /**
     * トークンでアクセスされた場合、クライアント編集画面を表示
     */
    public function showByToken($token)
    {
        $tokenRecord = ClientIntakeToken::with('client')->where('token', $token)->first();

        // トークンが存在しない、または紐づくクライアントが存在しない
        if (!$tokenRecord || !$tokenRecord->client) {
            return view('client-intake.errors.invalid-token', [
                'title' => 'このURLは無効です',
                'message' => 'URLが間違っているか、既に削除されています。トレーナーにお問い合わせください。',
            ]);
        }

        // 期限切れ
        if ($tokenRecord->isExpired()) {
            return view('client-intake.errors.invalid-token', [
                'title' => 'このURLは期限切れです',
                'message' => 'このURLの有効期限が切れています。トレーナーにお問い合わせください。',
            ]);
        }

        // 使用済み
        if ($tokenRecord->is_used) {
            return view('client-intake.errors.invalid-token', [
                'title' => 'このURLは既に使用されています',
                'message' => 'このURLで既に入力が完了しています。再度の入力はできません。',
            ]);
        }

        // 有効なトークン → 編集画面を表示（クライアントの現在値を初期表示）
        return view('client-intake.index-public', [
            'token' => $token,
            'tokenRecord' => $tokenRecord,
            'client' => $tokenRecord->client,
        ]);
    }

    /**
     * トークンを使用してクライアント情報を更新
     */
    public function updateByToken(Request $request, $token)
    {
        // トークンの有効性を再チェック
        $tokenRecord = ClientIntakeToken::with('client')->where('token', $token)->first();

        if (!$tokenRecord || !$tokenRecord->client || $tokenRecord->isExpired() || $tokenRecord->is_used) {
            return view('client-intake.errors.invalid-token', [
                'title' => 'このURLは無効です',
                'message' => 'このURLは無効か、既に使用されています。トレーナーにお問い合わせください。',
            ]);
        }

        // バリデーション（S-0306 と同じ項目。ただし internal_id・initial_consultation_date・主担当は受け付けない）
        $validated = $request->validate([
            // 基本情報
            'last_name' => 'required|string|max:50',
            'first_name' => 'nullable|string|max:50',
            'last_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'first_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:男,女,その他',

            // 連絡先
            'phone1' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'email' => 'nullable|email|max:255',
            'postal_code' => ['nullable', 'string', 'max:10', 'regex:/^[0-9\-]+$/'],
            'address1' => 'nullable|string|max:50',
            'address2' => 'nullable|string|max:50',
            'address3' => 'nullable|string|max:100',
            'address4' => 'nullable|string|max:100',
        ]);

        // トランザクション内でクライアント更新 + トークンを使用済み化
        DB::transaction(function () use ($validated, $tokenRecord) {
            $tokenRecord->client->update($validated);

            $tokenRecord->update([
                'is_used' => true,
            ]);
        });

        return view('client-intake.complete-public');
    }

}
