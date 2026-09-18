<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * トレーニー登録・編集の共通バリデーションルール（D-0700）。
 *
 * ClientRequest と同じ方針：ルーティング側のミドルウェア（practitioners）で
 * 認可を担保し、ここでは常に true を返す。
 *
 * attributes() の override について：
 *   `name` は `lang/ja/validation.php` の attributes に「氏名」として登録されているため、
 *   本 Request でだけ「名前」に上書きする（他画面のトレーナー氏名等の表示は変えない）。
 */
class TraineeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'breed' => 'nullable|string|max:100',
            'sex' => 'nullable|in:male,female,unknown',
            // 誕生日は未来日不可（保護犬等で不明な場合は未入力）
            'birth_date' => 'nullable|date|before_or_equal:today',
            'note' => 'nullable|string',
        ];
    }

    /**
     * 属性名の日本語ラベル。
     *
     * `name` はグローバル定義（`lang/ja/validation.php`）で「氏名」となっているが、
     * トレーニーの文脈では「名前」を使いたいため本 Request 内で上書きする
     * （グローバル定義を書き換えると他画面のトレーナー氏名等に影響するため）。
     */
    public function attributes(): array
    {
        return [
            'name' => '名前',
            'breed' => '犬種',
            'sex' => '性別',
            'birth_date' => '誕生日',
            'note' => '備考',
        ];
    }
}
