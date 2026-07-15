<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientIntakeToken;
use App\Services\ClientInternalIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientIntakeController extends Controller
{
    /**
     * トークンでアクセスされた場合���クライアント登録画面を表示
     */
    public function showByToken($token)
    {
        $tokenRecord = ClientIntakeToken::where('token', $token)->first();

        // トークンが存在しない
        if (!$tokenRecord) {
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
                'message' => 'このURLで既に登録が完了しています。重複登録を防ぐため、再度の入力はできません。',
            ]);
        }

        // 有効なトークン → 登録画面を表示
        return view('client-intake.index-public', [
            'token' => $token,
            'tokenRecord' => $tokenRecord,
        ]);
    }

    /**
     * トークンを使用してクライアント登録
     */
    public function storeByToken(Request $request, $token)
    {
        // トークンの有効性を再チェック
        $tokenRecord = ClientIntakeToken::where('token', $token)->first();

        if (!$tokenRecord || $tokenRecord->isExpired() || $tokenRecord->is_used) {
            return view('client-intake.errors.invalid-token', [
                'title' => 'このURLは無効です',
                'message' => 'このURLは無効か、既に使用されています。トレーナーにお問い合わせください。',
            ]);
        }

        // バリデーション（既存のstore()メソッドと同じルール）
        $validated = $request->validate([
            // 基本情報
            'last_name' => 'required|string|max:50',
            'first_name' => 'nullable|string|max:50',
            'last_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'first_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:男,女,その他',
            'initial_consultation_date' => 'required|date',

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

        // トランザクション内でクライアント登録 + トークン更新
        DB::transaction(function () use ($validated, $tokenRecord) {
            $newInternalId = (string) (new ClientInternalIdService())->generateNext();

            $client = Client::create(array_merge($validated, [
                'internal_id' => $newInternalId,
                'primary_trainer_id' => null,
            ]));

            // トークンを使用済みに更新
            $tokenRecord->update([
                'is_used' => true,
                'client_id' => $client->id,
            ]);
        });

        return view('client-intake.complete-public');
    }

}
