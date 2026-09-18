<?php

namespace App\Http\Controllers;

use App\Http\Requests\TraineeMeasurementRequest;
use App\Models\Trainee;
use App\Models\TraineeMeasurement;
use Illuminate\Http\RedirectResponse;

/**
 * トレーニー計測値（D-0800）の CRUD コントローラ。
 *
 * TraineeController の書き方に合わせる：
 * - store / update で `updated_by` はコントローラで明示代入（既存慣例）
 * - フラッシュキーは `success`
 * - destroy は物理削除。**権限は一般トレーナーも可**（設計書 6-16-7）
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

        return redirect()
            ->route('trainees.show', $trainee)
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

        return redirect()
            ->route('trainees.show', $measurement->trainee)
            ->with('success', '計測値を更新しました。');
    }

    /**
     * 計測値の削除（物理削除、一般トレーナーも可）
     *
     * 削除後は親トレーニーの詳細へ戻すため、削除前にトレーニーを退避する。
     */
    public function destroy(TraineeMeasurement $measurement): RedirectResponse
    {
        $trainee = $measurement->trainee;
        $measurement->delete();

        return redirect()
            ->route('trainees.show', $trainee)
            ->with('success', '計測値を削除しました。');
    }
}
