<?php

namespace App\Http\Controllers;

use App\Models\MediaRecord;
use App\Models\Trainer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * メディア管理コントローラー
 *
 * メディアは署名付きURLでオブジェクトストレージへ直アップロードする方式を採る。
 * 本コントローラは「署名発行（uploadUrl）」と「アップロード完了後のレコード作成（store）」
 * を提供する。
 */
class MediaRecordController extends Controller
{
    // アップロード署名付きURLの有効期限（分）
    const UPLOAD_URL_EXPIRES_MINUTES = 15;

    // 再生（GET）署名付きURLの有効期限（分）
    // 将来のクライアント向け公開ではより短くする可能性があるため、UPLOAD と独立して持つ
    const PLAY_URL_EXPIRES_MINUTES = 15;

    // オブジェクトストレージのディスク名
    const STORAGE_DISK = 'sakura';

    // storage_key の形式 media/YYYYMM/{uuid}.{ext}（store 時の形式検証用）
    const STORAGE_KEY_PATTERN = '#^media/\d{6}/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.[a-z0-9]+$#';

    // 一覧の1ページあたり件数（グリッド表示向け）
    const INDEX_PER_PAGE = 24;

    /**
     * メディア一覧画面（GET /media-records）
     *
     * 登録者フィルタ（trainer_id クエリ）で絞り込み、登録日時降順で一覧表示する。
     * 'all' は全件、整数値はそのトレーナーのみ、未指定はログイン中トレーナーをデフォルト。
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 詳細モーダルでクライアント名・登録者名を表示するため、N+1回避の eager load を入れる
        $query = MediaRecord::with(['client', 'trainer'])
            ->orderBy('created_at', 'desc');

        // 登録者フィルタ（既存の音声記録一覧と同型）
        $trainerId = $request->query('trainer_id');
        if ($trainerId === 'all') {
            // フィルタなし（NULL含む全件）
        } elseif ($trainerId) {
            $query->where('trainer_id', $trainerId);
        } else {
            // デフォルト: 自分が登録したもののみ
            $query->where('trainer_id', $user->id);
        }

        $mediaRecords = $query->paginate(self::INDEX_PER_PAGE)->appends($request->query());

        // 登録者プルダウン用トレーナー一覧（system_admin を除外）
        $trainers = Trainer::practitioners()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedTrainerId = $trainerId ?? $user->id;

        // 詳細モーダル用のメタ情報辞書（id → 表示用フィールドの連想配列）
        // ビューでJSに JSON で渡し、カードクリック時にidで lookup する
        $mediaModalData = $mediaRecords->getCollection()->mapWithKeys(function ($m) {
            return [$m->id => [
                'type' => $m->type,
                'mime_type' => $m->mime_type,
                'display_title' => $m->display_title,
                // 編集フォーム用に「raw な title（NULL あり得る）」と「client_id」を別途持つ
                'title_raw' => $m->title,
                'client_id' => $m->client_id,
                'original_filename' => $m->original_filename,
                'created_at' => $m->created_at->format('Y/m/d H:i'),
                'client_name' => $m->client
                    ? trim(($m->client->internal_id ?? '') . ' ' . $m->client->display_name)
                    : null,
                'trainer_name' => $m->trainer?->name,
            ]];
        });

        return view('media-records.index', compact('mediaRecords', 'trainers', 'selectedTrainerId', 'mediaModalData'));
    }

    /**
     * メディア登録画面（GET /media-records/create）
     *
     * クライアント選択は Select2 + 内部API `/api/clients/search` を使用するため、
     * コントローラから渡すデータは無い。
     */
    public function create(): View
    {
        return view('media-records.create');
    }

    /**
     * 署名付きURL発行（POST /api/media-records/upload-url）
     *
     * 申請値（mime_type / file_size）で形式・サイズを事前検証し、不適合は 422 で拒否。
     * 適合時は storage_key を採番して presigned PUT URL を発行する。
     */
    public function uploadUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'original_filename' => 'required|string|max:255',
            'mime_type' => 'required|string',
            'file_size' => 'required|integer|min:1',
        ], [
            'original_filename.required' => '元ファイル名を指定してください。',
            'original_filename.max' => '元ファイル名は255文字以内で指定してください。',
            'mime_type.required' => 'MIMEタイプを指定してください。',
            'file_size.required' => 'ファイルサイズを指定してください。',
            'file_size.integer' => 'ファイルサイズは整数で指定してください。',
            'file_size.min' => 'ファイルサイズは1バイト以上で指定してください。',
        ]);

        // 形式（mime_type）→ 種別判定。許可リスト外は拒否
        $type = MediaRecord::resolveTypeFromMime($validated['mime_type']);
        if ($type === null) {
            return response()->json([
                'error' => ['mime_type' => ['対応形式は写真(jpeg/png/heic)・動画(mp4/mov)のみです。']],
            ], 422);
        }

        // サイズ上限チェック（種別ごとに上限が異なる）
        $maxSize = MediaRecord::maxSizeForType($type);
        if ($validated['file_size'] > $maxSize) {
            $limitLabel = $type === MediaRecord::TYPE_PHOTO ? '20MB' : '1GB';
            return response()->json([
                'error' => ['file_size' => ["ファイルサイズは{$limitLabel}以下にしてください。"]],
            ], 422);
        }

        // storage_key 採番: media/YYYYMM/{uuid}.{ext}
        $extension = MediaRecord::extensionForMime($validated['mime_type']);
        $storageKey = sprintf('media/%s/%s.%s', now()->format('Ym'), (string) Str::uuid(), $extension);

        // 署名付きPUT URL発行（有効期限はクラス定数で管理）
        $expiresAt = now()->addMinutes(self::UPLOAD_URL_EXPIRES_MINUTES);
        $signed = Storage::disk(self::STORAGE_DISK)->temporaryUploadUrl($storageKey, $expiresAt);

        return response()->json([
            'data' => [
                'upload_url' => $signed['url'],
                'storage_key' => $storageKey,
            ],
        ]);
    }

    /**
     * メディアレコード作成（POST /api/media-records）
     *
     * 直アップロード完了後にブラウザから呼ばれる。
     * storage_key は upload-url で発行された形式に合致するかを正規表現で検証する
     * （形式検証のみ。ファイル実体の存在確認は今フェーズでは行わない）。
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'storage_key' => 'required|string',
            'original_filename' => 'required|string|max:255',
            'mime_type' => 'required|string',
            'file_size' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
        ], [
            'client_id.required' => 'クライアントを指定してください。',
            'client_id.exists' => '選択されたクライアントが存在しません。',
            'storage_key.required' => '保存キーを指定してください。',
            'original_filename.required' => '元ファイル名を指定してください。',
            'original_filename.max' => '元ファイル名は255文字以内で指定してください。',
            'mime_type.required' => 'MIMEタイプを指定してください。',
            'file_size.required' => 'ファイルサイズを指定してください。',
            'file_size.integer' => 'ファイルサイズは整数で指定してください。',
            'file_size.min' => 'ファイルサイズは1バイト以上で指定してください。',
            'title.max' => '表示名は255文字以内で入力してください。',
        ]);

        // storage_key の形式検証（upload-url で発行した形式と一致するか）
        if (!preg_match(self::STORAGE_KEY_PATTERN, $validated['storage_key'])) {
            return response()->json([
                'error' => ['storage_key' => ['保存キーの形式が不正です。']],
            ], 422);
        }

        // mime_type から種別確定
        $type = MediaRecord::resolveTypeFromMime($validated['mime_type']);
        if ($type === null) {
            return response()->json([
                'error' => ['mime_type' => ['対応形式は写真(jpeg/png/heic)・動画(mp4/mov)のみです。']],
            ], 422);
        }

        $mediaRecord = MediaRecord::create([
            'client_id' => $validated['client_id'],
            'trainer_id' => Auth::id(),
            'type' => $type,
            'title' => $validated['title'] ?? null,
            'original_filename' => $validated['original_filename'],
            'file_path' => $validated['storage_key'],
            'mime_type' => $validated['mime_type'],
            'file_size' => $validated['file_size'],
        ]);

        return response()->json(['data' => $mediaRecord], 201);
    }

    /**
     * メディア更新（PUT /media-records/{id}）
     *
     * 表示名（title）と持ち主クライアント（client_id）のみ変更可能。
     * 種別・ファイル・元ファイル名・mime_type は変更しない（ファイル差し替えは別フロー）。
     */
    public function update(Request $request, MediaRecord $mediaRecord): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'nullable|string|max:255',
        ], [
            'client_id.required' => 'クライアントを選択してください。',
            'client_id.exists' => '選択されたクライアントが存在しません。',
            'title.max' => '表示名は255文字以内で入力してください。',
        ]);

        $mediaRecord->update([
            'client_id' => $validated['client_id'],
            'title' => $validated['title'] ?? null,
        ]);

        return response()->json(['data' => $mediaRecord->fresh()]);
    }

    /**
     * メディア削除（DELETE /media-records/{id}）
     *
     * ファイル先削除 → 成功してからレコード削除（既存音声と同型）。
     * sakura ディスクは config で throw=>true のため、ファイル削除失敗は例外で500に。
     * その場合レコードは残り「DB ↔ ストレージ整合」の不変条件を保つ。
     */
    public function destroy(MediaRecord $mediaRecord): JsonResponse
    {
        if (Storage::disk(self::STORAGE_DISK)->exists($mediaRecord->file_path)) {
            Storage::disk(self::STORAGE_DISK)->delete($mediaRecord->file_path);
        }
        $mediaRecord->delete();

        return response()->json(['success' => true]);
    }

    /**
     * 再生（GET /api/media-records/{id}/play）
     *
     * ストレージ上のメディア実体への presigned GET URL を発行して返す。
     * クライアント（ブラウザ）はこのURLへ直接アクセスして再生・表示する。
     * 存在確認は行わない（store 時の方針Bと整合。孤児レコードは sakura が 403/404 を返す）。
     */
    public function play(MediaRecord $mediaRecord): JsonResponse
    {
        $expiresAt = now()->addMinutes(self::PLAY_URL_EXPIRES_MINUTES);
        $url = Storage::disk(self::STORAGE_DISK)->temporaryUrl($mediaRecord->file_path, $expiresAt);

        return response()->json([
            'data' => [
                'url' => $url,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }
}
