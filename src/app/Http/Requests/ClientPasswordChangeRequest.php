<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

/**
 * クライアントパスワード変更（S-1406 / 6-15-11）バリデーション。
 *
 * 現在のパスワードは client guard で照合する。新しいパスワードは
 * S-1403 初回設定と同じ強度要件（StrongPassword）と、確認用との一致を求める。
 *
 * $errorBag = 'password' で他フォームとエラー表示を分離。
 * ビュー側は `$errors->password` で参照する。
 */
class ClientPasswordChangeRequest extends FormRequest
{
    protected $errorBag = 'password';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Laravel の current_password ルールは第 1 引数にガード名を取れる
            'current_password' => ['required', 'string', 'current_password:client'],
            'new_password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => '現在のパスワードを入力してください。',
            'current_password.current_password' => '現在のパスワードが正しくありません。',
            'new_password.required' => '新しいパスワードを入力してください。',
            'new_password.confirmed' => '新しいパスワード（確認）が一致しません。',
        ];
    }
}
