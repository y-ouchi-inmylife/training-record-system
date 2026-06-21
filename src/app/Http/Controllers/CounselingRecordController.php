<?php

namespace App\Http\Controllers;

use App\Models\AudioRecord;
use App\Models\Client;
use App\Models\ConsultationType;
use App\Models\Counselor;
use App\Models\CounselingRecord;
use App\Models\Phase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CounselingRecordController extends Controller
{
    /**
     * トレーニング記録検索・一覧画面（S-0402）
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

        $query = CounselingRecord::with(['client', 'consultationType', 'counselor1', 'counselor2', 'phase', 'participants']);

        // 内部ID（部分一致）
        if ($request->filled('internal_id')) {
            $internalId = $request->input('internal_id');
            $query->whereHas('client', function ($q) use ($internalId) {
                $q->where('internal_id', 'like', "%{$internalId}%");
            });
        }

        // 氏名検索（本人・家族の姓名・かな 8カラムを横断的に部分一致）
        if ($request->filled('name')) {
            $name = $request->input('name');
            $query->whereHas('client', function ($q) use ($name) {
                $q->where(function ($sub) use ($name) {
                    $sub->where('last_name', 'like', "%{$name}%")
                        ->orWhere('first_name', 'like', "%{$name}%")
                        ->orWhere('last_name_kana', 'like', "%{$name}%")
                        ->orWhere('first_name_kana', 'like', "%{$name}%")
                        ->orWhere('family_last_name', 'like', "%{$name}%")
                        ->orWhere('family_first_name', 'like', "%{$name}%")
                        ->orWhere('family_last_name_kana', 'like', "%{$name}%")
                        ->orWhere('family_first_name_kana', 'like', "%{$name}%");
                });
            });
        }

        // トレーニング日（期間指定）
        if ($request->filled('date_from')) {
            $query->where('consultation_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('consultation_date', '<=', $request->input('date_to'));
        }

        // 担当者フィルター（担当者1または担当者2に含まれていれば表示）
        if ($request->filled('counselor_id')) {
            $counselorId = $request->input('counselor_id');
            $query->where(function ($q) use ($counselorId) {
                $q->where('counselor1_id', $counselorId)
                  ->orWhere('counselor2_id', $counselorId);
            });
        }

        // 参加状況フィルター
        if ($request->filled('attendance')) {
            $query->where('attendance', $request->input('attendance'));
        }

        // 参加形態フィルター
        if ($request->filled('consultation_format')) {
            $query->where('consultation_format', $request->input('consultation_format'));
        }

        // キーワード検索（トレーニング記録・所感の全文検索）
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('record_content', 'like', "%{$keyword}%")
                  ->orWhere('impression', 'like', "%{$keyword}%")
                  ->orWhere('consultation_detail', 'like', "%{$keyword}%");
            });
        }

        // ソート
        $sortBy = $request->input('sort', 'consultation_date');
        $sortDir = $request->input('direction', 'desc');
        $allowedSorts = ['consultation_date', 'internal_id', 'client_name', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'consultation_date';
        }
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'internal_id') {
            // クライアントの内部IDで数値ソート（文字列型だが数値のみ格納）
            $query->leftJoin('clients', 'counseling_records.client_id', '=', 'clients.id')
                ->select('counseling_records.*')
                ->orderByRaw('CAST(clients.internal_id AS UNSIGNED) ' . $sortDir);
        } elseif ($sortBy === 'client_name') {
            // クライアント一覧の氏名ソートと同じロジック（本人/家族の分岐 + 日本語照合順序）
            $query->leftJoin('clients', 'counseling_records.client_id', '=', 'clients.id')
                ->select('counseling_records.*')
                ->orderByRaw("
                    CASE
                        WHEN clients.family_relationship = '本人' OR clients.family_relationship IS NULL
                            THEN clients.last_name
                        WHEN clients.family_last_name IS NOT NULL AND clients.family_last_name <> ''
                            THEN clients.family_last_name
                        ELSE clients.last_name
                    END COLLATE utf8mb4_ja_0900_as_cs " . $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        // 2次ソート: トレーニング日が同じレコードはトレーニング時刻で並び替える（NULLはMySQLのDESCで最後、ASCで最初）
        if ($sortBy !== 'consultation_date') {
            $query->orderBy('consultation_date', 'desc');
        }
        $query->orderBy('consultation_time', $sortDir);

        $records = $query->paginate(20)->withQueryString();
        $consultationTypes = ConsultationType::orderBy('sort_order')->get();
        $counselors = Counselor::practitioners()->orderBy('display_order')->orderBy('name')->get();

        return view('counseling-records.index', compact('records', 'consultationTypes', 'counselors'));
    }

    /**
     * トレーニング記録新規登録画面（S-0401 登録モード）
     *
     * 業務方針: トレーニング記録は必ずクライアント詳細から登録する設計（自由選択モード廃止）。
     * ?client_id= 未指定・不存在クライアントの場合はクライアント一覧へリダイレクト。
     */
    public function create(Request $request): View|RedirectResponse
    {
        // クライアント詳細画面や録音画面からの遷移時はパラメータを受け取る
        $selectedClientId = $request->input('client_id');

        if (!$selectedClientId) {
            return redirect()->route('clients.index')
                ->with('error', 'トレーニング記録を登録するクライアントを選択してください');
        }

        $selectedClient = Client::find($selectedClientId);
        if (!$selectedClient) {
            return redirect()->route('clients.index')
                ->with('error', '指定されたクライアントが見つかりません');
        }

        $consultationTypes = ConsultationType::orderBy('sort_order')->get();
        $counselors = Counselor::practitioners()->orderBy('display_order')->orderBy('name')->get();
        $phases = Phase::orderBy('sort_order')->get();

        $audioRecordId = $request->input('audio_record_id');

        return view('counseling-records.create', compact(
            'consultationTypes', 'counselors', 'phases', 'selectedClientId', 'selectedClient', 'audioRecordId'
        ));
    }

    /**
     * トレーニング記録登録処理
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $validated['updated_by'] = auth()->id();

        try {
            DB::transaction(function () use ($validated, $request) {
                $record = CounselingRecord::create($validated);

                // 参加者の登録
                $this->syncParticipants($record, $request->input('participants', []));
            });
        } catch (\Exception $e) {
            \Log::error('トレーニング記録登録エラー', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()
                ->withInput()
                ->with('error', 'トレーニング記録の登録に失敗しました。');
        }

        return redirect()
            ->route('clients.show', $validated['client_id'])
            ->with('success', 'トレーニング記録を登録しました。');
    }

    /**
     * トレーニング記録詳細画面
     */
    public function show(CounselingRecord $counselingRecord): View
    {
        $counselingRecord->load([
            'client', 'consultationType', 'counselor1', 'counselor2',
            'phase', 'participants',
        ]);

        return view('counseling-records.show', compact('counselingRecord'));
    }

    /**
     * トレーニング記録編集画面（S-0404 編集モード）
     */
    public function edit(CounselingRecord $counselingRecord): View
    {
        $counselingRecord->load(['client', 'participants']);

        $consultationTypes = ConsultationType::orderBy('sort_order')->get();
        $counselors = Counselor::practitioners()->orderBy('display_order')->orderBy('name')->get();
        $phases = Phase::orderBy('sort_order')->get();

        return view('counseling-records.edit', compact(
            'counselingRecord', 'consultationTypes', 'counselors', 'phases'
        ));
    }

    /**
     * トレーニング記録更新処理
     */
    public function update(Request $request, CounselingRecord $counselingRecord): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $validated['updated_by'] = auth()->id();

        try {
            DB::transaction(function () use ($validated, $request, $counselingRecord) {
                $counselingRecord->update($validated);

                // 参加者の再作成（全削除 → 再登録）
                $counselingRecord->participants()->delete();
                $this->syncParticipants($counselingRecord, $request->input('participants', []));

                // 参加者の変更もトレーニング記録の更新として扱う
                // 本体カラムが dirty でなくても、参加者だけ変更されたケースで
                // updated_at/updated_by を確実に更新する（bugs.md No.191 対応）
                $counselingRecord->updated_by = auth()->id();
                $counselingRecord->touch();
            });
        } catch (\Exception $e) {
            \Log::error('トレーニング記録更新エラー', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()
                ->withInput()
                ->with('error', 'トレーニング記録の更新に失敗しました。');
        }

        return redirect()
            ->route('clients.show', $counselingRecord->client_id)
            ->with('success', 'トレーニング記録を更新しました。');
    }

    /**
     * トレーニング記録削除処理（管理トレーナーのみ）
     */
    public function destroy(CounselingRecord $counselingRecord): RedirectResponse
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, '管理者のみ削除できます。');
        }

        $clientId = $counselingRecord->client_id;
        $counselingRecord->delete();

        return redirect()
            ->route('clients.show', $clientId)
            ->with('success', 'トレーニング記録を削除しました。');
    }

    /**
     * 録音画面からトレーニング記録を自動登録（API）
     */
    public function autoCreate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audio_record_id' => 'required|exists:audio_records,id',
            'client_id' => 'required|exists:clients,id',
            'consultation_date' => 'required|date',
            'consultation_time' => 'nullable|date_format:H:i',
            'consultation_format' => 'required|in:対面,ビデオ通話,電話,メール,同行,訪問,その他',
            'counselor1_id' => 'required|exists:counselors,id',
            'counselor2_id' => 'nullable|exists:counselors,id|different:counselor1_id',
            'participants' => 'nullable|array',
            'participants.*.participant_type' => 'nullable|in:本人,支援者,母,父,配偶者,きょうだい,子,祖父母,その他',
            'participants.*.participant_detail' => 'nullable|string|max:255',
        ]);

        try {
            $record = DB::transaction(function () use ($validated) {
                $audioRecord = AudioRecord::findOrFail($validated['audio_record_id']);

                $record = CounselingRecord::create([
                    'client_id' => $validated['client_id'],
                    'consultation_date' => $validated['consultation_date'],
                    'consultation_time' => $validated['consultation_time'] ?? null,
                    'consultation_format' => $validated['consultation_format'],
                    'counselor1_id' => $validated['counselor1_id'],
                    'counselor2_id' => $validated['counselor2_id'] ?? null,
                    'record_content' => $audioRecord->summary_text,
                    'attendance' => '参加',
                ]);

                // 参加者の登録
                $this->syncParticipants($record, $validated['participants'] ?? []);

                return $record;
            });

            return response()->json([
                'success' => true,
                'counseling_record_id' => $record->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'トレーニング記録の作成に失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 参加者の登録
     */
    private function syncParticipants(CounselingRecord $record, array $participants): void
    {
        foreach ($participants as $participant) {
            if (empty($participant['participant_type'])) {
                continue;
            }
            $record->participants()->create([
                'participant_type' => $participant['participant_type'],
                'participant_detail' => $participant['participant_detail'] ?? null,
            ]);
        }
    }

    /**
     * バリデーションルール
     */
    private function validationRules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'consultation_date' => 'required|date',
            'consultation_time' => 'nullable|date_format:H:i',
            'is_intake' => 'boolean',
            'is_followup' => 'boolean',
            'consultation_type_id' => 'nullable|exists:consultation_types,id',
            'consultation_detail' => 'nullable|string|max:255',
            'counselor1_id' => 'required|exists:counselors,id',
            'counselor2_id' => 'nullable|exists:counselors,id|different:counselor1_id',
            'record_content' => 'nullable|string',
            'impression' => 'nullable|string',
            'phase_id' => 'nullable|exists:phases,id',
            'attendance' => 'required|in:参加,キャンセル（連絡あり）,キャンセル（連絡なし）',
            'consultation_format' => 'required|in:対面,ビデオ通話,電話,メール,同行,訪問,その他',
            'consultation_format_detail' => 'nullable|string|max:255',
            'participants' => 'nullable|array',
            'participants.*.participant_type' => 'nullable|in:本人,支援者,母,父,配偶者,きょうだい,子,祖父母,その他',
            'participants.*.participant_detail' => 'nullable|string|max:255',
        ];
    }
}
