<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * メディア記録モデル
 *
 * クライアントに紐付く写真・動画ファイルをライブラリ型で管理する。
 * ファイル実体はオブジェクトストレージに保存し、本モデルはそのメタデータを保持する。
 */
class MediaRecord extends Model
{
    use HasFactory;

    // テーブル名（規約では MediaRecord → media_records と解決されるため省略可能だが、
    // 既存モデル（AudioRecord）に揃えて明示する）
    protected $table = 'media_records';

    // メディア種別定数（DBのCHECK制約 type IN ('photo','video') と対応）
    const TYPE_PHOTO = 'photo';
    const TYPE_VIDEO = 'video';

    // 許可するMIMEタイプ（写真: jpeg/png/heic/heif、動画: mp4/mov）
    // image/heif はiOSがHEICファイルでも送出するケースの実機確認に基づき許可
    const PHOTO_MIME_TYPES = ['image/jpeg', 'image/png', 'image/heic', 'image/heif'];
    const VIDEO_MIME_TYPES = ['video/mp4', 'video/quicktime'];

    // サイズ上限（要件 6-14-1）
    const MAX_PHOTO_SIZE = 20 * 1024 * 1024;           // 写真: 20MB
    const MAX_VIDEO_SIZE = 1024 * 1024 * 1024;         // 動画: 1GB

    // MIMEタイプ → 拡張子マッピング（storage_key 採番時の拡張子決定に使用）
    const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
    ];

    // ブラウザがそのまま表示・再生できるMIMEタイプ
    // heic/heif/quicktime は許可形式だが、変換しないとブラウザで開けない（変換対応は今後フェーズ）
    const BROWSER_DISPLAYABLE_MIME_TYPES = ['image/jpeg', 'image/png', 'video/mp4'];

    /**
     * MIMEタイプがブラウザで直接表示・再生可能か
     */
    public static function isBrowserDisplayable(string $mime): bool
    {
        return in_array($mime, self::BROWSER_DISPLAYABLE_MIME_TYPES, true);
    }

    /**
     * MIMEタイプから種別（photo/video）を判定する。許可リスト外は null を返す
     */
    public static function resolveTypeFromMime(string $mime): ?string
    {
        if (in_array($mime, self::PHOTO_MIME_TYPES, true)) {
            return self::TYPE_PHOTO;
        }
        if (in_array($mime, self::VIDEO_MIME_TYPES, true)) {
            return self::TYPE_VIDEO;
        }
        return null;
    }

    /**
     * MIMEタイプから拡張子を取得する。未対応は null を返す
     */
    public static function extensionForMime(string $mime): ?string
    {
        return self::MIME_TO_EXTENSION[$mime] ?? null;
    }

    /**
     * 種別ごとのサイズ上限を取得する
     */
    public static function maxSizeForType(string $type): ?int
    {
        return match ($type) {
            self::TYPE_PHOTO => self::MAX_PHOTO_SIZE,
            self::TYPE_VIDEO => self::MAX_VIDEO_SIZE,
            default => null,
        };
    }

    protected $fillable = [
        'client_id',
        'trainer_id',
        'type',
        'title',
        'original_filename',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // --- リレーション ---

    /**
     * 持ち主クライアント
     *
     * クライアント削除時は SET NULL になるため、null を返しうる。
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * アップロードしたトレーナー（登録者）
     *
     * トレーナー削除時は SET NULL になるため、null を返しうる。
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    // --- アクセサ ---

    /**
     * 表示名（title 未入力時は元ファイル名にフォールバック）
     *
     * title が NULL もしくは空文字のときは original_filename を返す。
     */
    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: $this->original_filename;
    }
}
