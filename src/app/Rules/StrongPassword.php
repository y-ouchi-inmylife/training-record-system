<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * パスワード強度バリデーションルール
 */
class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < 8) {
            $fail('パスワードは8文字以上で入力してください。');
            return;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $fail('パスワードに大文字を1文字以上含めてください。');
            return;
        }

        if (!preg_match('/[a-z]/', $value)) {
            $fail('パスワードに小文字を1文字以上含めてください。');
            return;
        }

        if (!preg_match('/[0-9]/', $value)) {
            $fail('パスワードに数字を1文字以上含めてください。');
            return;
        }

        if (!preg_match('/[!@#$%^&*()\-_=+\[\]{};:\'",.<>\/?\\\\|`~]/', $value)) {
            $fail('パスワードに記号を1文字以上含めてください。');
            return;
        }

        // よくあるパスワードを禁止
        $commonPasswords = ['password', 'password1!', 'admin123!', 'Password1!', 'Admin123!'];
        if (in_array(strtolower($value), array_map('strtolower', $commonPasswords))) {
            $fail('このパスワードはよく使われるため使用できません。');
        }
    }
}
