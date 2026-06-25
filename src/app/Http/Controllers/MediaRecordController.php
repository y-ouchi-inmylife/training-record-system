<?php

namespace App\Http\Controllers;

use App\Models\MediaRecord;
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
    // 署名付きURLの有効期限（分）
    const UPLOAD_URL_EXPIRES_MINUTES = 15;

    // オブジェクトストレージのディスク名
    const STORAGE_DISK = 'sakura';

    // storage_key の形式 media/YYYYMM/{uuid}.{ext}（store 時の形式検証用）
    const STORAGE_KEY_PATTERN = '#^media/\d{6}/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.[a-z0-9]+$#';

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
}
