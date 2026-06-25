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
