<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * クライアント基本情報（連絡先）の変更（S-1406 / 6-15-9）バリデーション。
 *
 * 電話番号・郵便番号・住所を必須で受け取り、氏名・メールアドレス・パスワードは
 * 受け付けない（別フォーム）。ルールは S-1403 初回設定の連絡先項目と揃える。
 *
 * $errorBag = 'profile' を指定し、他フォーム（メールアドレス変更・パスワード変更）と
 * エラー表示を分離する。ビュー側は `$errors->profile` で参照する。
 */
class ClientProfileRequest extends FormRequest
{
    protected $errorBag = 'profile';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone1' => ['required', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'postal_code' => ['required', 'string', 'regex:/^\d{3}-?\d{4}$/'],
            'address1' => 'required|string|max:50',
            'address2' => 'required|string|max:50',
            'address3' => 'required|string|max:100',
            'address4' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'phone1.required' => '電話番号を入力してください。',
            'phone1.regex' => '電話番号の形式が正しくありません。',
            'phone2.regex' => '電話番号（予備）の形式が正しくありません。',
            'postal_code.required' => '郵便番号を入力してください。',
            'postal_code.regex' => '郵便番号の形式が正しくありません。',
            'address1.required' => '都道府県を選択してください。',
            'address2.required' => '市区町村を入力してください。',
            'address3.required' => '町名・番地を入力してください。',
        ];
    }
}
