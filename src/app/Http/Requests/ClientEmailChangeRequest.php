<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * クライアントメールアドレス変更 申し込み（S-1406 / 6-15-10）バリデーション。
 *
 * 新しいメールアドレスと現在のパスワードを受け取り、申し込みを行う。
 * 実際の切替は確認リンクを開いた時点で行うため、この時点では clients.email は
 * 書き換えない（コントローラ側の処理）。
 *
 * $errorBag = 'email' で他フォームとエラー表示を分離。
 * ビュー側は `$errors->email` で参照する。
 */
class ClientEmailChangeRequest extends FormRequest
{
    protected $errorBag = 'email';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $client = Auth::guard('client')->user();

        return [
            // 新しいメールアドレス：形式・重複（自クライアント除外）・現在と異なること
            'new_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($client?->id),
                function ($attribute, $value, $fail) use ($client) {
                    if ($client && $client->email === $value) {
                        $fail('現在と同じメールアドレスです。');
                    }
                },
            ],
            // 現在のパスワードは client guard で照合
            'current_password' => ['required', 'string', 'current_password:client'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_email.required' => 'メールアドレスを入力してください。',
            'new_email.email' => 'メールアドレスの形式が正しくありません。',
            'new_email.unique' => 'このメールアドレスは登録できません。担当トレーナーにご連絡ください。',
            'current_password.required' => '現在のパスワードを入力してください。',
            'current_password.current_password' => '現在のパスワードが正しくありません。',
        ];
    }
}
