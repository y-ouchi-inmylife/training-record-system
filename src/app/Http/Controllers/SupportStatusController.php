<?php

namespace App\Http\Controllers;

use App\Models\SupportStatus;
use Illuminate\Http\Request;

/**
 * 支援状態マスタ管理コントローラー
 */
class SupportStatusController extends Controller
{
    /**
     * 一覧表示
     */
    public function index()
    {
        $supportStatuses = SupportStatus::withCount('clients')->orderBy('sort_order')->orderBy('id')->get();

        return view('master.support-statuses.index', compact('supportStatuses'));
    }

    /**
     * 新規追加
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:support_statuses,name',
        ], [
            'name.required' => '支援状態名は必須です。',
            'name.max' => '支援状態名は50文字以内で入力してください。',
            'name.unique' => 'この支援状態名は既に登録されています。',
        ]);

        // 新規追加時はデフォルトでダッシュボード表示ON（DBデフォルト値に委ねる）

        // 並び順は末尾に追加
        $maxOrder = SupportStatus::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;

        SupportStatus::create($validated);

        return redirect()->route('master.support-statuses.index')
            ->with('success', '支援状態を追加しました。');
    }

    /**
     * 更新
     */
    public function update(Request $request, SupportStatus $supportStatus)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:support_statuses,name,' . $supportStatus->id,
        ], [
            'name.required' => '支援状態名は必須です。',
            'name.max' => '支援状態名は50文字以内で入力してください。',
            'name.unique' => 'この支援状態名は既に登録されています。',
        ]);

        // チェックボックス未送信時はfalseにする
        $validated['show_in_dashboard'] = $request->boolean('show_in_dashboard');

        $supportStatus->update($validated);

        return redirect()->route('master.support-statuses.index')
            ->with('success', '支援状態を更新しました。');
    }

    /**
     * 削除
     */
    public function destroy(SupportStatus $supportStatus)
    {
        // 使用中の支援状態は削除不可
        if ($supportStatus->clients()->exists()) {
            return redirect()->route('master.support-statuses.index')
                ->with('error', 'この支援状態はクライアント情報で使用されているため削除できません。');
        }

        $supportStatus->delete();

        return redirect()->route('master.support-statuses.index')
            ->with('success', '支援状態を削除しました。');
    }

    /**
     * 並び順変更（上へ移動）
     */
    public function moveUp(SupportStatus $supportStatus)
    {
        $previous = SupportStatus::where('sort_order', '<', $supportStatus->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previous) {
            $tempOrder = $supportStatus->sort_order;
            $supportStatus->update(['sort_order' => $previous->sort_order]);
            $previous->update(['sort_order' => $tempOrder]);
        }

        return redirect()->route('master.support-statuses.index');
    }

    /**
     * 並び順変更（下へ移動）
     */
    public function moveDown(SupportStatus $supportStatus)
    {
        $next = SupportStatus::where('sort_order', '>', $supportStatus->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($next) {
            $tempOrder = $supportStatus->sort_order;
            $supportStatus->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $tempOrder]);
        }

        return redirect()->route('master.support-statuses.index');
    }
}
