<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CounselingRecord;
use App\Models\Counselor;
use App\Models\Phase;
use App\Models\SupportStatus;
use App\Services\ClientInternalIdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    /**
     * クライアント一覧・検索画面（S-0304）
     */
    public function index(Request $request): View
    {
        // 日付フィルタの相関チェック（開始日 ≦ 終了日）
        $request->validate(
            [
                'date_from' => 'nullable|date',
                'date_to'   => 'nullable|date|after_or_equal:date_from',
            ],
            [
                'date_to.after_or_equal' => '開始日は終了日以前の日付を指定してください',
            ]
        );

        $query = Client::with(['primaryCounselor', 'supportStatus'])
            ->addSelect([
                'last_consultation_date' => CounselingRecord::select('consultation_date')
                    ->whereColumn('client_id', 'clients.id')
                    ->orderBy('consultation_date', 'desc')
                    ->orderBy('consultation_time', 'desc')
                    ->limit(1),
                'latest_phase_id' => CounselingRecord::select('phase_id')
                    ->whereColumn('client_id', 'clients.id')
                    ->whereNotNull('phase_id')
                    ->orderBy('consultation_date', 'desc')
                    ->orderBy('consultation_time', 'desc')
                    ->limit(1),
            ]);

        // 内部ID検索（部分一致）
        if ($request->filled('internal_id')) {
            $query->where('internal_id', 'like', '%' . $request->input('internal_id') . '%');
        }

        // 名前検索（姓名・かな・家族名の部分一致）
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('last_name', 'like', "%{$keyword}%")
                  ->orWhere('first_name', 'like', "%{$keyword}%")
                  ->orWhere('last_name_kana', 'like', "%{$keyword}%")
                  ->orWhere('first_name_kana', 'like', "%{$keyword}%")
                  ->orWhere('family_last_name', 'like', "%{$keyword}%")
                  ->orWhere('family_first_name', 'like', "%{$keyword}%")
                  ->orWhere('family_last_name_kana', 'like', "%{$keyword}%")
                  ->orWhere('family_first_name_kana', 'like', "%{$keyword}%");
            });
        }

        // 支援状態フィルター
        if ($request->filled('support_status_id')) {
            $query->where('support_status_id', $request->input('support_status_id'));
        }

        // 主担当トレーナーフィルター
        if ($request->filled('primary_counselor_id')) {
            $query->where('primary_counselor_id', $request->input('primary_counselor_id'));
        }

        // 最終トレーニング日の期間指定（最新のトレーニング記録日付で絞り込み）
        if ($request->filled('date_from')) {
            $query->whereRaw(
                '(SELECT MAX(cr.consultation_date) FROM counseling_records cr WHERE cr.client_id = clients.id) >= ?',
                [$request->input('date_from')]
            );
        }
        if ($request->filled('date_to')) {
            $query->whereRaw(
                '(SELECT MAX(cr.consultation_date) FROM counseling_records cr WHERE cr.client_id = clients.id) <= ?',
                [$request->input('date_to')]
            );
        }

        // ソート
        $sortBy = $request->input('sort', 'created_at');
        $sortDir = $request->input('direction', 'desc');
        $allowedSorts = ['internal_id', 'last_name', 'last_name_kana', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';
        // internal_id は文字列型だが数値のみを格納するため、数値として比較する
        if ($sortBy === 'internal_id') {
            $query->orderByRaw('CAST(internal_id AS UNSIGNED) ' . $sortDir);
        } elseif ($sortBy === 'last_name') {
            // display_name アクセサの表示ロジックに合わせて並び替える
            // - 本人（またはfamily_relationshipがNULL）: last_name
            // - 本人以外で家族姓あり: family_last_name
            // - 本人以外で家族姓空: last_name にフォールバック
            $query->orderByRaw("
                CASE
                    WHEN family_relationship = '本人' OR family_relationship IS NULL
                        THEN last_name
                    WHEN family_last_name IS NOT NULL AND family_last_name <> ''
                        THEN family_last_name
                    ELSE last_name
                END COLLATE utf8mb4_ja_0900_as_cs " . $sortDir);
        } elseif ($sortBy === 'last_name_kana') {
            // display_name_kana アクセサの表示ロジックに合わせて並び替える
            // - 本人（またはfamily_relationshipがNULL）: last_name_kana
            // - 本人以外で家族かなあり: family_last_name_kana
            // - 本人以外で家族かな空: last_name_kana にフォールバック
            $query->orderByRaw("
                CASE
                    WHEN family_relationship = '本人' OR family_relationship IS NULL
                        THEN last_name_kana
                    WHEN family_last_name_kana IS NOT NULL AND family_last_name_kana <> ''
                        THEN family_last_name_kana
                    ELSE last_name_kana
                END COLLATE utf8mb4_ja_0900_as_cs " . $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $clients = $query->paginate(20)->withQueryString();
        $counselors = Counselor::practitioners()->orderBy('display_order')->orderBy('name')->get();
        $phases = Phase::pluck('name', 'id');
        $supportStatuses = SupportStatus::orderBy('sort_order')->get();

        return view('clients.index', compact('clients', 'counselors', 'phases', 'supportStatuses'));
    }

    /**
     * クライアント新規登録画面（S-0301 ステップ形式ウィザード）
     */
    public function create(): View
    {
        $counselors = Counselor::practitioners()->orderBy('display_order')->orderBy('name')->get();
        $supportStatuses = SupportStatus::orderBy('sort_order')->get();

        // 次の内部IDを計算
        $maxId = DB::selectOne('SELECT MAX(CAST(internal_id AS UNSIGNED)) as max_id FROM clients')->max_id;
        $nextInternalId = ($maxId ?? 0) + 1;

        return view('clients.create', compact('counselors', 'supportStatuses', 'nextInternalId'));
    }

    /**
     * クライアント登録処理
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        // 本人との関係に応じた姓の必須チェック
        if ($error = $this->validateNameByRelationship($request)) {
            return back()->withErrors($error)->withInput();
        }

        $validated['updated_by'] = auth()->id();

        // トランザクション内で内部IDを採番（競合回避）
        $client = DB::transaction(function () use ($validated) {
            $validated['internal_id'] = (string) (new ClientInternalIdService())->generateNext();

            return Client::create($validated);
        });

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'クライアントを登録しました。');
    }

    /**
     * クライアント詳細画面（S-0305）
     */
    public function show(Client $client): View
    {
        $client->load(['primaryCounselor', 'supportStatus', 'counselingRecords' => function ($query) {
            $query->with(['consultationType', 'counselor1', 'counselor2', 'phase', 'participants'])
                  ->orderBy('consultation_date', 'desc')
                  ->orderBy('consultation_time', 'desc');
        }]);

        $counselors = Counselor::practitioners()->orderBy('display_order')->orderBy('name')->get();

        return view('clients.show', compact('client', 'counselors'));
    }

    /**
     * クライアント編集画面
     */
    public function edit(Client $client): View
    {
        $counselors = Counselor::practitioners()->orderBy('display_order')->orderBy('name')->get();
        $supportStatuses = SupportStatus::orderBy('sort_order')->get();
        return view('clients.edit', compact('client', 'counselors', 'supportStatuses'));
    }

    /**
     * クライアント更新処理
     */
    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate(
            array_merge(
                ['internal_id' => 'required|numeric|unique:clients,internal_id,' . $client->id],
                $this->validationRules()
            ),
            $this->internalIdMessages()
        );

        // 本人との関係に応じた姓の必須チェック
        if ($error = $this->validateNameByRelationship($request)) {
            return back()->withErrors($error)->withInput();
        }

        $validated['updated_by'] = auth()->id();
        $client->update($validated);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'クライアント情報を更新しました。');
    }

    /**
     * クライアント削除処理（管理トレーナーのみ）
     */
    public function destroy(Client $client): RedirectResponse
    {
        // 管理トレーナーのみ削除可能
        if (!auth()->user()->isAdmin()) {
            abort(403, '管理者のみ削除できます。');
        }

        // トレーニング記録が存在する場合は削除不可
        if ($client->counselingRecords()->exists()) {
            return redirect()
                ->route('clients.show', $client)
                ->with('error', 'このクライアントにはトレーニング記録が登録されているため削除できません。');
        }

        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'クライアントを削除しました。');
    }

    /**
     * バリデーションルール
     */
    /**
     * クライアント検索API（Select2用）
     */
    public function apiSearch(Request $request): JsonResponse
    {
        // ID指定の場合（バリデーションエラー後の復元用）
        if ($request->filled('id')) {
            $client = Client::find($request->input('id'));
            if ($client) {
                return response()->json([
                    'results' => [['id' => $client->id, 'text' => $client->internal_id . ' ' . $client->display_name]],
                ]);
            }
            return response()->json(['results' => []]);
        }

        $query = $request->input('q', '');

        $clients = Client::where(function ($q) use ($query) {
            $q->where('internal_id', 'like', "%{$query}%")
              ->orWhere('last_name', 'like', "%{$query}%")
              ->orWhere('first_name', 'like', "%{$query}%")
              ->orWhere('last_name_kana', 'like', "%{$query}%")
              ->orWhere('first_name_kana', 'like', "%{$query}%")
              ->orWhere('family_last_name', 'like', "%{$query}%")
              ->orWhere('family_first_name', 'like', "%{$query}%")
              ->orWhere('family_last_name_kana', 'like', "%{$query}%")
              ->orWhere('family_first_name_kana', 'like', "%{$query}%");
        })
        ->orderBy('internal_id')
        ->limit(20)
        ->get();

        return response()->json([
            'results' => $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'text' => $client->internal_id . ' ' . $client->display_name,
                ];
            }),
        ]);
    }

    /**
     * 内部IDのバリデーションメッセージ
     */
    /**
     * 本人との関係に応じた姓の必須チェック
     */
    private function validateNameByRelationship(Request $request): ?array
    {
        if ($request->family_relationship === '本人') {
            if (empty($request->last_name)) {
                return ['last_name' => '本人との関係が「本人」の場合、姓（本人）は必須です。'];
            }
        } else {
            if (empty($request->family_last_name)) {
                return ['family_last_name' => '本人との関係が「本人以外」の場合、姓（家族など）は必須です。'];
            }
        }
        return null;
    }

    private function internalIdMessages(): array
    {
        return [
            'internal_id.required' => '内部IDを入力してください。',
            'internal_id.numeric' => '内部IDは数値で入力してください。',
            'internal_id.unique' => 'この内部IDは既に使用されています。',
        ];
    }

    private function validationRules(): array
    {
        return [
            // カテゴリー1: 基本情報
            'last_name' => 'nullable|string|max:50',
            'first_name' => 'nullable|string|max:50',
            'last_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'first_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'family_last_name' => 'nullable|string|max:50',
            'family_first_name' => 'nullable|string|max:50',
            'family_last_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'family_first_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'family_relationship' => 'required|in:本人,母,父,配偶者,きょうだい,子,祖父母,その他',
            'family_relationship_detail' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'initial_age' => 'nullable|integer|min:0|max:150',
            'gender' => 'nullable|in:男,女,その他',
            'initial_consultation_date' => 'required|date',

            // カテゴリー2: 連絡先
            'phone1' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'phone3' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'email' => 'nullable|email|max:255',
            'postal_code' => ['nullable', 'string', 'max:10', 'regex:/^[0-9\-]+$/'],
            'address1' => 'nullable|string|max:50',
            'address2' => 'nullable|string|max:50',
            'address3' => 'nullable|string|max:100',
            'address4' => 'nullable|string|max:100',
            'nearest_station' => 'nullable|string|max:50',

            // カテゴリー3: 学歴
            'education_level' => 'nullable|in:中学,全日制高校,定時制高校,通信制高校,高専,専門学校,大学,短大,大学院,その他',
            'education_detail' => 'nullable|string',
            'education_status' => 'nullable|in:卒業,中退,在学中,休学中',
            'education_dropout_expected' => 'nullable|boolean',

            // カテゴリー4: 職歴
            'employment_type' => 'nullable|in:正社員・正規職員,契約社員・嘱託社員,パート・アルバイト,派遣社員,その他・詳細不明',
            'employment_hours' => 'nullable|in:週20時間以上,週20時間未満,不定期',
            'employment_period' => 'nullable|in:有期雇用（3ヶ月未満）,有期雇用（3～6ヶ月未満）,有期雇用（6ヶ月～1年未満）,有期雇用（1年以上）,無期雇用',
            'unemployment_period' => 'nullable|in:6ヶ月未満,6ヶ月～1年,1～3年,3～5年,5～10年,10年以上',
            'employment_detail' => 'nullable|string',

            // カテゴリー5: 障害・医療情報
            'disability_physical' => 'nullable|in:あり,なし',
            'disability_physical_grade' => 'nullable|string|max:100',
            'disability_mental' => 'nullable|in:あり,なし',
            'disability_mental_grade' => 'nullable|string|max:100',
            'disability_intellectual' => 'nullable|in:あり,なし',
            'disability_intellectual_grade' => 'nullable|string|max:100',
            'disability_detail' => 'nullable|string',
            'hospital' => 'nullable|string',
            'medication' => 'nullable|string',

            // カテゴリー6: 生活状況
            'financial_status' => 'nullable|in:生活保護を受給している,逼迫している,特に困っていない',
            'financial_detail' => 'nullable|string',
            'hikikomori' => 'nullable|in:あり,なし',
            'school_refusal' => 'nullable|in:あり,なし',
            'bullying' => 'nullable|in:あり,なし',

            // カテゴリー7: 支援管理
            'primary_counselor_id' => 'nullable|exists:counselors,id',
            'cooperating_agencies' => 'nullable|string',
            'support_status_id' => 'nullable|exists:support_statuses,id',
        ];
    }
}
