<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use Illuminate\Http\Request;

/**
 * フェーズマスタ管理コントローラー
 */
class PhaseController extends Controller
{
    /**
     * 一覧表示
     */
    public function index()
    {
        $phases = Phase::withCount('trainingRecords')->orderBy('sort_order')->orderBy('id')->get();

        return view('master.phases.index', compact('phases'));
    }

    /**
     * 新規追加
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:phases,name',
        ], [
            'name.required' => 'フェーズ名は必須です。',
            'name.max' => 'フェーズ名は100文字以内で入力してください。',
            'name.unique' => 'このフェーズ名は既に登録されています。',
        ]);

        // 並び順は末尾に追加
        $maxOrder = Phase::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;

        Phase::create($validated);

        return redirect()->route('master.phases.index')
            ->with('success', 'フェーズを追加しました。');
    }

    /**
     * 更新
     */
    public function update(Request $request, Phase $phase)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:phases,name,' . $phase->id,
        ], [
            'name.required' => 'フェーズ名は必須です。',
            'name.max' => 'フェーズ名は100文字以内で入力してください。',
            'name.unique' => 'このフェーズ名は既に登録されています。',
        ]);

        $phase->update($validated);

        return redirect()->route('master.phases.index')
            ->with('success', 'フェーズを更新しました。');
    }

    /**
     * 削除
     */
    public function destroy(Phase $phase)
    {
        // 使用中のフェーズは削除不可
        if ($phase->trainingRecords()->exists()) {
            return redirect()->route('master.phases.index')
                ->with('error', 'このフェーズはトレーニング記録で使用されているため削除できません。');
        }

        $phase->delete();

        return redirect()->route('master.phases.index')
            ->with('success', 'フェーズを削除しました。');
    }

    /**
     * 並び順変更（上へ移動）
     */
    public function moveUp(Phase $phase)
    {
        $previous = Phase::where('sort_order', '<', $phase->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previous) {
            $tempOrder = $phase->sort_order;
            $phase->update(['sort_order' => $previous->sort_order]);
            $previous->update(['sort_order' => $tempOrder]);
        }

        return redirect()->route('master.phases.index');
    }

    /**
     * 並び順変更（下へ移動）
     */
    public function moveDown(Phase $phase)
    {
        $next = Phase::where('sort_order', '>', $phase->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($next) {
            $tempOrder = $phase->sort_order;
            $phase->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $tempOrder]);
        }

        return redirect()->route('master.phases.index');
    }
}
