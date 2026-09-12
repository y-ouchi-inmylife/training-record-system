<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

/**
 * クライアント初回設定（S-1403）のバリデーションルール。
 *
 * トレーナー側の [[ClientRequest]] と必須項目が異なる（電話番号・住所が必須）ため、
 * 別に用意する。ルールは設計書 api-design.md `POST /client-portal/setup/{token}`。
 *
 * 認可はルートの公開設定・トークン検証で担保しており、本 FormRequest では常に true を返す。
 */
class ClientInitialSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // パスワード
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],

            // 氏名
            'last_name' => 'required|string|max:50',
            'first_name' => 'nullable|string|max:50',
            'last_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'first_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],

            // 連絡先（電話番号・郵便番号・住所は必須。建物名以外は必須）
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
            'password.required' => 'パスワードを入力してください。',
            'password.confirmed' => 'パスワード（確認）が一致しません。',
            'last_name.required' => '姓を入力してください。',
            'last_name_kana.regex' => 'せいはひらがなで入力してください。',
            'first_name_kana.regex' => 'めいはひらがなで入力してください。',
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
