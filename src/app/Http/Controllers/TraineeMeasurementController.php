<?php

namespace App\Http\Controllers;

use App\Http\Requests\TraineeMeasurementRequest;
use App\Models\Trainee;
use App\Models\TraineeMeasurement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * トレーニー計測値（D-0800）の CRUD コントローラ。
 *
 * TraineeController の書き方に合わせる：
 * - store / update で `updated_by` はコントローラで明示代入（既存慣例）
 * - フラッシュキーは `success`
 * - destroy は物理削除。**権限は一般トレーナーも可**（設計書 6-16-7）
 *
 * ## リダイレクト先の切り替え（2026-09 追加、api-design.md「計測値エンドポイントの `return_to` 仕様」参照）
 *
 * 3 メソッドとも、リクエストの hidden `return_to` を見て**成功時のリダイレクト先**を切り替える：
 *   - `return_to=client`：S-0305 会員詳細画面へ戻す（S-0305 のトレーニーカードから登録した場合）
 *   - `return_to=trainee`（既定）：S-0309 トレーニー詳細画面へ戻す（従来動作、S-0309 から）
 *   - それ以外・欠落時は `trainee` として扱う（安全側フォールバック）
 *
 * **URL 文字列そのものは受け付けない**（open redirect 回避のため、ルート名から URL を生成する）。
 *
 * 現在のスコープでは `store` のみが S-0305 から呼ばれるが、`update` / `destroy` にも
 * 同じ `return_to` 仕組みを実装しておく（将来 S-0305 から編集・削除もできるようにする
 * 可能性を残すため。コード一貫性の維持コストは小さい）。
 */
class TraineeMeasurementController extends Controller
{
    /**
     * 計測値の登録
     */
    public function store(TraineeMeasurementRequest $request, Trainee $trainee): RedirectResponse
    {
        $validated = $request->validated();
        $validated['trainee_id'] = $trainee->id;
        $validated['updated_by'] = auth()->id();

        TraineeMeasurement::create($validated);

        return $this->redirectAfter($request, $trainee)
            ->with('success', '計測値を登録しました。');
    }

    /**
     * 計測値の更新
     */
    public function update(TraineeMeasurementRequest $request, TraineeMeasurement $measurement): RedirectResponse
    {
        $validated = $request->validated();
        $validated['updated_by'] = auth()->id();
        $measurement->update($validated);

        return $this->redirectAfter($request, $measurement->trainee)
            ->with('success', '計測値を更新しました。');
    }

    /**
     * 計測値の削除（物理削除、一般トレーナーも可）
     *
     * 削除後のリダイレクトで trainee 情報を使うため、削除前にトレーニーを退避する。
     * `return_to=client` が渡された場合は `$trainee->client_id` も参照するが、
     * トレーニーの eloquent モデルが読み込まれた時点で client_id はプロパティに
     * 入っているため、レコード削除後も参照できる（`$measurement->delete()` は
     * measurement 側の削除で trainee には影響しない）。
     */
    public function destroy(Request $request, TraineeMeasurement $measurement): RedirectResponse
    {
        $trainee = $measurement->trainee;
        $measurement->delete();

        return $this->redirectAfter($request, $trainee)
            ->with('success', '計測値を削除しました。');
    }

    /**
     * 送信元（`return_to`）に応じてリダイレクト先を決定する共通ヘルパー。
     *
     * `return_to` の値は 'client' / 'trainee' の 2 値のみを受け付け、
     * それ以外・欠落時は 'trainee' として扱う（安全側フォールバック）。
     * URL 文字列は受け付けない（open redirect 回避）。
     */
    private function redirectAfter(Request $request, Trainee $trainee): RedirectResponse
    {
        if ($request->input('return_to') === 'client') {
            return redirect()->route('clients.show', $trainee->client_id);
        }
        return redirect()->route('trainees.show', $trainee);
    }
}
