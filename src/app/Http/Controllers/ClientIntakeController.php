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
            'initial_age' => 'nullable|integer|min:0|max:150',
            'gender' => 'nullable|in:男,女,その他',
            'initial_consultation_date' => 'required|date',

            // 連絡先
            'phone1' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'phone3' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'email' => 'nullable|email|max:255',
            'postal_code' => ['nullable', 'string', 'max:10', 'regex:/^[0-9\-]+$/'],
            'address1' => 'nullable|string|max:50',
            'address2' => 'nullable|string|max:50',
            'address3' => 'nullable|string|max:100',
            'address4' => 'nullable|string|max:100',
            'nearest_station' => 'nullable|string|max:50',

            // 学歴
            'education_level' => 'nullable|in:中学,全日制高校,定時制高校,通信制高校,高専,専門学校,大学,短大,大学院,その他',
            'education_detail' => 'nullable|string',
            'education_status' => 'nullable|in:卒業,中退,在学中,休学中',
            'education_dropout_expected' => 'nullable|boolean',

            // 職歴
            'employment_type' => 'nullable|in:正社員・正規職員,契約社員・嘱託社員,パート・アルバイト,派遣社員,その他・詳細不明',
            'employment_hours' => 'nullable|in:週20時間以上,週20時間未満,不定期',
            'employment_period' => 'nullable|in:有期雇用（3ヶ月未満）,有期雇用（3～6ヶ月未満）,有期雇用（6ヶ月～1年未満）,有期雇用（1年以上）,無期雇用',
            'unemployment_period' => 'nullable|in:6ヶ月未満,6ヶ月～1年,1～3年,3～5年,5～10年,10年以上',
            'employment_detail' => 'nullable|string',

            // 障害・医療情報
            'disability_physical' => 'nullable|in:あり,なし',
            'disability_physical_grade' => 'nullable|string|max:100',
            'disability_mental' => 'nullable|in:あり,なし',
            'disability_mental_grade' => 'nullable|string|max:100',
            'disability_intellectual' => 'nullable|in:あり,なし',
            'disability_intellectual_grade' => 'nullable|string|max:100',
            'disability_detail' => 'nullable|string',
            'hospital' => 'nullable|string',
            'medication' => 'nullable|string',

            // 生活状況
            'financial_status' => 'nullable|in:生活保護を受給している,逼迫している,特に困っていない',
            'financial_detail' => 'nullable|string',
            'hikikomori' => 'nullable|in:あり,なし',
            'school_refusal' => 'nullable|in:あり,なし',
            'bullying' => 'nullable|in:あり,なし',
        ]);

        // トランザクション内でクライアント登録 + トークン更新
        DB::transaction(function () use ($validated, $tokenRecord) {
            $newInternalId = (string) (new ClientInternalIdService())->generateNext();

            $client = Client::create(array_merge($validated, [
                'internal_id' => $newInternalId,
                'primary_counselor_id' => null,
                'support_status_id' => null,
                'cooperating_agencies' => null,
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
