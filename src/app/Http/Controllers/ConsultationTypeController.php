<?php

namespace App\Http\Controllers;

use App\Models\ConsultationType;
use Illuminate\Http\Request;

/**
 * トレーニング内容マスタ管理コントローラー
 */
class ConsultationTypeController extends Controller
{
    /**
     * 一覧表示
     */
    public function index()
    {
        $types = ConsultationType::withCount('counselingRecords')->orderBy('sort_order')->orderBy('id')->get();

        return view('master.consultation-types.index', compact('types'));
    }

    /**
     * 新規追加
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:consultation_types,name',
        ], [
            'name.required' => 'トレーニング内容名は必須です。',
            'name.max' => 'トレーニング内容名は50文字以内で入力してください。',
            'name.unique' => 'このトレーニング内容名は既に登録されています。',
        ]);

        // 並び順は末尾に追加
        $maxOrder = ConsultationType::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;

        ConsultationType::create($validated);

        return redirect()->route('master.consultation-types.index')
            ->with('success', 'トレーニング内容を追加しました。');
    }

    /**
     * 更新
     */
    public function update(Request $request, ConsultationType $consultationType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:consultation_types,name,' . $consultationType->id,
        ], [
            'name.required' => 'トレーニング内容名は必須です。',
            'name.max' => 'トレーニング内容名は50文字以内で入力してください。',
            'name.unique' => 'このトレーニング内容名は既に登録されています。',
        ]);

        $consultationType->update($validated);

        return redirect()->route('master.consultation-types.index')
            ->with('success', 'トレーニング内容を更新しました。');
    }

    /**
     * 削除
     */
    public function destroy(ConsultationType $consultationType)
    {
        // 使用中のトレーニング内容は削除不可
        if ($consultationType->counselingRecords()->exists()) {
            return redirect()->route('master.consultation-types.index')
                ->with('error', 'このトレーニング内容はトレーニング記録で使用されているため削除できません。');
        }

        $consultationType->delete();

        return redirect()->route('master.consultation-types.index')
            ->with('success', 'トレーニング内容を削除しました。');
    }

    /**
     * 並び順変更（上へ移動）
     */
    public function moveUp(ConsultationType $consultationType)
    {
        $previous = ConsultationType::where('sort_order', '<', $consultationType->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previous) {
            $tempOrder = $consultationType->sort_order;
            $consultationType->update(['sort_order' => $previous->sort_order]);
            $previous->update(['sort_order' => $tempOrder]);
        }

        return redirect()->route('master.consultation-types.index');
    }

    /**
     * 並び順変更（下へ移動）
     */
    public function moveDown(ConsultationType $consultationType)
    {
        $next = ConsultationType::where('sort_order', '>', $consultationType->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($next) {
            $tempOrder = $consultationType->sort_order;
            $consultationType->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $tempOrder]);
        }

        return redirect()->route('master.consultation-types.index');
    }
}
