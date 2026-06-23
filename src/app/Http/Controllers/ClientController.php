<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TrainingRecord;
use App\Models\Trainer;
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

        $query = Client::with(['primaryTrainer', 'supportStatus'])
            ->addSelect([
                'last_training_date' => TrainingRecord::select('training_date')
                    ->whereColumn('client_id', 'clients.id')
                    ->orderBy('training_date', 'desc')
                    ->orderBy('training_time', 'desc')
                    ->limit(1),
                'latest_phase_id' => TrainingRecord::select('phase_id')
                    ->whereColumn('client_id', 'clients.id')
                    ->whereNotNull('phase_id')
                    ->orderBy('training_date', 'desc')
                    ->orderBy('training_time', 'desc')
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
                  ->orWhere('first_name_kana', 'like', "%{$keyword}%");
            });
        }

        // 支援状態フィルター
        if ($request->filled('support_status_id')) {
            $query->where('support_status_id', $request->input('support_status_id'));
        }

        // 主担当トレーナーフィルター
        if ($request->filled('primary_trainer_id')) {
            $query->where('primary_trainer_id', $request->input('primary_trainer_id'));
        }

        // 最終トレーニング日の期間指定（最新のトレーニング記録日付で絞り込み）
        if ($request->filled('date_from')) {
            $query->whereRaw(
                '(SELECT MAX(cr.training_date) FROM training_records cr WHERE cr.client_id = clients.id) >= ?',
                [$request->input('date_from')]
            );
        }
        if ($request->filled('date_to')) {
            $query->whereRaw(
                '(SELECT MAX(cr.training_date) FROM training_records cr WHERE cr.client_id = clients.id) <= ?',
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
            // 日本語照合順序で姓順に並べる
            $query->orderByRaw("last_name COLLATE utf8mb4_ja_0900_as_cs " . $sortDir);
        } elseif ($sortBy === 'last_name_kana') {
            // 日本語照合順序で姓かな順に並べる
            $query->orderByRaw("last_name_kana COLLATE utf8mb4_ja_0900_as_cs " . $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $clients = $query->paginate(20)->withQueryString();
        $trainers = Trainer::practitioners()->orderBy('display_order')->orderBy('name')->get();
        $phases = Phase::pluck('name', 'id');
        $supportStatuses = SupportStatus::orderBy('sort_order')->get();

        return view('clients.index', compact('clients', 'trainers', 'phases', 'supportStatuses'));
    }

    /**
     * クライアント新規登録画面（S-0301 ステップ形式ウィザード）
     */
    public function create(): View
    {
        $trainers = Trainer::practitioners()->orderBy('display_order')->orderBy('name')->get();
        $supportStatuses = SupportStatus::orderBy('sort_order')->get();

        // 次の内部IDを計算
        $maxId = DB::selectOne('SELECT MAX(CAST(internal_id AS UNSIGNED)) as max_id FROM clients')->max_id;
        $nextInternalId = ($maxId ?? 0) + 1;

        return view('clients.create', compact('trainers', 'supportStatuses', 'nextInternalId'));
    }

    /**
     * クライアント登録処理
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

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
        $client->load(['primaryTrainer', 'supportStatus', 'trainingRecords' => function ($query) {
            $query->with(['trainingType', 'trainer1', 'trainer2', 'phase'])
                  ->orderBy('training_date', 'desc')
                  ->orderBy('training_time', 'desc');
        }]);

        $trainers = Trainer::practitioners()->orderBy('display_order')->orderBy('name')->get();

        return view('clients.show', compact('client', 'trainers'));
    }

    /**
     * クライアント編集画面
     */
    public function edit(Client $client): View
    {
        $trainers = Trainer::practitioners()->orderBy('display_order')->orderBy('name')->get();
        $supportStatuses = SupportStatus::orderBy('sort_order')->get();
        return view('clients.edit', compact('client', 'trainers', 'supportStatuses'));
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
        if ($client->trainingRecords()->exists()) {
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
              ->orWhere('first_name_kana', 'like', "%{$query}%");
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
            'last_name' => 'required|string|max:50',
            'first_name' => 'nullable|string|max:50',
            'last_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'first_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[\p{Hiragana}\s　]+$/u'],
            'birth_date' => 'nullable|date',
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

            // カテゴリー7: 支援管理
            'primary_trainer_id' => 'nullable|exists:trainers,id',
            'support_status_id' => 'nullable|exists:support_statuses,id',
        ];
    }
}
