<?php

namespace App\Http\Controllers;

use App\Http\Requests\TraineeRequest;
use App\Models\Client;
use App\Models\Trainee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * トレーニー（D-0700）の CRUD コントローラ。
 *
 * ClientController の書き方に合わせている：
 * - `store` / `update` で `updated_by` はコントローラで明示代入（モデルイベント不使用）
 * - `destroy` は管理者のみ（`isAdmin()` チェック）
 * - フラッシュメッセージのキーは `success` / `error`
 */
class TraineeController extends Controller
{
    /**
     * トレーニー登録画面（S-0308）
     *
     * URL パラメータの会員に紐づくトレーニーを新規登録する。
     */
    public function create(Client $client): View
    {
        return view('trainees.create', compact('client'));
    }

    /**
     * トレーニー登録処理
     */
    public function store(TraineeRequest $request, Client $client): RedirectResponse
    {
        $validated = $request->validated();
        $validated['client_id'] = $client->id;
        $validated['updated_by'] = auth()->id();

        Trainee::create($validated);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'トレーニーを登録しました。');
    }

    /**
     * トレーニー詳細画面（S-0309）
     *
     * 段階②で計測値の一覧・登録・編集・削除も本画面に含める。登録・編集は
     * モーダル（`_measurement-modal.blade.php`）で行うため、`create` / `edit` の
     * 画面遷移は持たない。
     */
    public function show(Trainee $trainee): View
    {
        $trainee->load(['client', 'measurements']);

        // 削除確認ダイアログに件数を含めるため、コントローラで数えて渡す。
        $measurementCount = $trainee->measurements->count();

        // モーダルの新規登録時の初期値（今日の日付・現在時刻）。Blade 内で now() を
        // 直接呼ばず、コントローラで組み立てて渡す（設計書のガイダンスに沿う）。
        $now = Carbon::now();
        $defaultMeasuredDate = $now->format('Y-m-d');
        $defaultMeasuredTime = $now->format('H:i');

        return view('trainees.show', compact(
            'trainee',
            'measurementCount',
            'defaultMeasuredDate',
            'defaultMeasuredTime'
        ));
    }

    /**
     * トレーニー編集画面（S-0310）
     */
    public function edit(Trainee $trainee): View
    {
        $trainee->load('client');

        return view('trainees.edit', compact('trainee'));
    }

    /**
     * トレーニー更新処理
     */
    public function update(TraineeRequest $request, Trainee $trainee): RedirectResponse
    {
        $validated = $request->validated();
        $validated['updated_by'] = auth()->id();
        $trainee->update($validated);

        return redirect()
            ->route('trainees.show', $trainee)
            ->with('success', 'トレーニー情報を更新しました。');
    }

    /**
     * トレーニー削除処理（管理者のみ）
     *
     * 削除後は元の会員詳細画面へ戻すため、削除前に client_id を退避してから使う。
     */
    public function destroy(Trainee $trainee): RedirectResponse
    {
        // 管理トレーナーのみ削除可能（ClientController::destroy と同じ形・同じ文言）
        if (! auth()->user()->isAdmin()) {
            abort(403, '管理者のみ削除できます。');
        }

        $clientId = $trainee->client_id;
        $trainee->delete();

        return redirect()
            ->route('clients.show', $clientId)
            ->with('success', 'トレーニーを削除しました。');
    }
}
