<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Counselor;
use App\Models\CounselingRecord;
use App\Models\LoginAttempt;
use App\Rules\StrongPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * トレーナーアカウント管理コントローラー
 */
class CounselorController extends Controller
{
    /**
     * 有効なトレーナー一覧を取得（API）
     */
    public function apiList(): JsonResponse
    {
        $counselors = Counselor::practitioners()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($counselors);
    }

    /**
     * トレーナー一覧
     */
    public function index(): View
    {
        $counselors = Counselor::where('role', '!=', 'system_admin')
            ->withCount([
                'primaryClients',
                'counselingRecordsAsCounselor1',
                'counselingRecordsAsCounselor2',
            ])->orderBy('display_order')->orderBy('name')->get();

        $adminCount = $counselors->where('role', 'admin')->count();

        return view('trainers.index', compact('counselors', 'adminCount'));
    }

    /**
     * トレーナー新規登録画面
     */
    public function create(): View
    {
        return view('trainers.create');
    }

    /**
     * トレーナー登録処理
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login_id' => 'required|string|max:50|regex:/^[a-zA-Z0-9_]+$/|unique:counselors,login_id',
            'name' => 'required|string|max:100',
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
            'role' => 'required|in:admin,staff',
        ], [
            'login_id.required' => 'ログインIDは必須です。',
            'login_id.unique' => 'このログインIDは既に使用されています。',
            'login_id.regex' => 'ログインIDに使用できない文字が含まれています。',
            'login_id.max' => 'ログインIDは50文字以内で入力してください。',
            'name.required' => '名前を入力してください。',
            'name.max' => '名前は100文字以内で入力してください。',
            'password.required' => 'パスワードは必須です。',
            'password.confirmed' => 'パスワード（確認）が一致しません。',
            'role.required' => '権限は必須です。',
            'role.in' => '権限は管理者または一般を選択してください。',
        ]);

        $validated['must_change_password'] = true;
        $validated['display_order'] = (Counselor::max('display_order') ?? 0) + 1;
        Counselor::create($validated);

        return redirect()->route('trainers.index')
            ->with('success', 'トレーナーを登録しました。初回ログイン時にパスワード変更が求められます。');
    }

    /**
     * トレーナー編集画面
     */
    public function edit(Counselor $counselor)
    {
        if ($counselor->isSystemAdmin()) {
            return redirect()->route('trainers.index')
                ->with('error', 'システム管理者アカウントは編集できません。');
        }

        return view('trainers.edit', compact('counselor'));
    }

    /**
     * トレーナー更新処理
     */
    public function update(Request $request, Counselor $counselor): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'role' => 'required|in:admin,staff',
        ], [
            'name.required' => '名前を入力してください。',
            'name.max' => '名前は100文字以内で入力してください。',
            'role.required' => '権限は必須です。',
            'role.in' => '権限は管理者または一般を選択してください。',
        ]);

        // 最後の管理者を一般に変更することを防止
        if ($counselor->role === 'admin' && $validated['role'] === 'staff') {
            $adminCount = Counselor::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return redirect()->route('trainers.edit', $counselor)
                    ->with('error', '管理者は最低1名必要です。権限を変更できません。');
            }
        }

        $counselor->update([
            'name' => $validated['name'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('trainers.index')
            ->with('success', 'トレーナー情報を更新しました。');
    }

    /**
     * トレーナー削除
     */
    public function destroy(Counselor $counselor): RedirectResponse
    {
        // system_adminは削除できない
        if ($counselor->isSystemAdmin()) {
            return redirect()->route('trainers.index')
                ->with('error', 'システム管理者アカウントは削除できません。');
        }

        // 自分自身は削除不可
        if ($counselor->id === auth()->id()) {
            return redirect()->route('trainers.index')
                ->with('error', '自分自身を削除することはできません。');
        }

        // 管理トレーナーが1名の場合は削除不可
        if ($counselor->role === 'admin') {
            $adminCount = Counselor::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return redirect()->route('trainers.index')
                    ->with('error', '管理者は最低1名必要です。削除できません。');
            }
        }

        // クライアントの主担当トレーナーになっているかチェック
        $primaryClientsCount = Client::where('primary_counselor_id', $counselor->id)->count();
        if ($primaryClientsCount > 0) {
            return redirect()->route('trainers.index')
                ->with('error', "{$counselor->name} は {$primaryClientsCount} 件のクライアントの主担当トレーナーです。先に主担当を変更してから削除してください。");
        }

        // トレーニング記録の担当者になっているかチェック（担当者1・担当者2）
        $recordsCount = CounselingRecord::where('counselor1_id', $counselor->id)
            ->orWhere('counselor2_id', $counselor->id)
            ->count();
        if ($recordsCount > 0) {
            return redirect()->route('trainers.index')
                ->with('error', "{$counselor->name} は {$recordsCount} 件のトレーニング記録の担当者です。削除できません。");
        }

        $counselor->delete();

        return redirect()->route('trainers.index')
            ->with('success', "{$counselor->name} を削除しました。");
    }

    /**
     * アカウントロック解除
     */
    public function unlock(Counselor $counselor): RedirectResponse
    {
        if (!$counselor->is_locked) {
            return redirect()->route('trainers.index')
                ->with('error', 'このアカウントはロックされていません。');
        }

        $counselor->update(['is_locked' => false]);

        // 失敗履歴をクリア
        LoginAttempt::where('counselor_id', $counselor->id)
            ->where('success', false)
            ->delete();

        return redirect()->route('trainers.index')
            ->with('success', $counselor->name . ' のアカウントロックを解除しました。');
    }

    /**
     * アカウント有効/無効の切り替え
     */
    public function toggleActive(Counselor $counselor): RedirectResponse
    {
        // system_adminは無効化できない
        if ($counselor->isSystemAdmin()) {
            return redirect()->route('trainers.index')
                ->with('error', 'システム管理者アカウントは無効化できません。');
        }

        // 自分自身は無効化できない
        if ($counselor->id === auth()->id()) {
            return redirect()->route('trainers.index')
                ->with('error', '自分自身のアカウントを無効化することはできません。');
        }

        // 最後の管理者を無効化することを防止
        if ($counselor->role === 'admin' && $counselor->is_active) {
            $activeAdminCount = Counselor::where('role', 'admin')
                ->where('is_active', true)
                ->count();
            if ($activeAdminCount <= 1) {
                return redirect()->route('trainers.index')
                    ->with('error', '管理者は最低1名必要です。無効化できません。');
            }
        }

        $counselor->update(['is_active' => !$counselor->is_active]);
        $status = $counselor->is_active ? '有効化' : '無効化';

        return redirect()->route('trainers.index')
            ->with('success', $counselor->name . ' のアカウントを' . $status . 'しました。');
    }

    /**
     * パスワードリセット画面
     */
    public function showResetPassword(Counselor $counselor): View|RedirectResponse
    {
        // system_adminはパスワードをリセットできない
        if ($counselor->isSystemAdmin()) {
            return redirect()->route('trainers.index')
                ->with('error', 'システム管理者アカウントはパスワードをリセットできません。');
        }

        return view('trainers.reset-password', compact('counselor'));
    }

    /**
     * パスワードリセット処理
     */
    public function resetPassword(Request $request, Counselor $counselor): RedirectResponse
    {
        // system_adminはパスワードをリセットできない
        if ($counselor->isSystemAdmin()) {
            return redirect()->route('trainers.index')
                ->with('error', 'システム管理者アカウントはパスワードをリセットできません。');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ], [
            'password.required' => '新しいパスワードは必須です。',
            'password.confirmed' => 'パスワード（確認）が一致しません。',
        ]);

        $counselor->update([
            'password' => $validated['password'],
            'must_change_password' => true,
        ]);

        return redirect()->route('trainers.index')
            ->with('success', $counselor->name . ' のパスワードをリセットしました。次回ログイン時にパスワード変更が求められます。');
    }

    /**
     * 表示順を1つ上に移動
     */
    public function moveUp(Counselor $counselor): RedirectResponse
    {
        if ($counselor->isSystemAdmin()) {
            return redirect()->route('trainers.index');
        }

        return $this->swapAdjacent($counselor, -1);
    }

    /**
     * 表示順を1つ下に移動
     */
    public function moveDown(Counselor $counselor): RedirectResponse
    {
        if ($counselor->isSystemAdmin()) {
            return redirect()->route('trainers.index');
        }

        return $this->swapAdjacent($counselor, 1);
    }

    /**
     * 一覧上の隣接トレーナーと表示順を入れ替え、display_orderを1,2,3...に振り直す
     *
     * @param int $offset -1=上に移動、+1=下に移動
     */
    private function swapAdjacent(Counselor $counselor, int $offset): RedirectResponse
    {
        // indexと同じソート順で全トレーナーを取得（一覧上の位置を特定するため）
        $counselors = Counselor::where('role', '!=', 'system_admin')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->values();

        $index = $counselors->search(fn ($c) => $c->id === $counselor->id);
        $targetIndex = $index + $offset;

        // 対象が見つからない、もしくは移動先が範囲外なら何もしない
        if ($index === false || $targetIndex < 0 || $targetIndex >= $counselors->count()) {
            return redirect()->route('trainers.index');
        }

        // 一覧上で位置を入れ替え
        $reordered = $counselors->all();
        [$reordered[$index], $reordered[$targetIndex]] = [$reordered[$targetIndex], $reordered[$index]];

        // display_orderを 1, 2, 3... と振り直し（重複や歯抜けを解消）
        foreach ($reordered as $i => $c) {
            $c->display_order = $i + 1;
            $c->save();
        }

        return redirect()->route('trainers.index')
            ->with('success', '表示順を変更しました。');
    }
}
