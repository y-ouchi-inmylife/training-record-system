<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * トレーニーモデル（D-0700）
 *
 * 会員に紐づくトレーニング対象（当面は犬）。トレーニング記録（training_records）
 * は会員単位で、トレーニー単位ではない（設計書 requirements.md 6-16 参照）。
 */
class Trainee extends Model
{
    /*
    |--------------------------------------------------------------------------
    | 性別（sex）の定数と日本語ラベル
    |--------------------------------------------------------------------------
    | 値・ラベルの単一情報源。Blade からは sexLabels() を参照する。
    | AccessLog::actionLabels() と同じ発想で、ビューに条件分岐を書き散らさない。
    */

    public const SEX_MALE = 'male';
    public const SEX_FEMALE = 'female';
    public const SEX_UNKNOWN = 'unknown';

    /**
     * 性別コード → 日本語ラベルのマッピング
     */
    public static function sexLabels(): array
    {
        return [
            self::SEX_MALE => 'オス',
            self::SEX_FEMALE => 'メス',
            self::SEX_UNKNOWN => '不明',
        ];
    }

    // 写真保存先の Filesystem ディスク名。
    // 既存メディアと同じ `media` ディスク（S3 互換）を共用し、キーの名前空間だけ
    // `trainees/` に分けて保存する（詳細は db-schema.md D-0700 注記「トレーニー写真の扱い」参照）。
    // MediaRecord::STORAGE_DISK と同じ流儀の定数として明示する。
    const STORAGE_DISK = 'media';

    // トレーニー写真の presigned 表示 URL の有効期限（分）
    // 既存の MediaRecordController::PLAY_URL_EXPIRES_MINUTES と揃える。
    const PHOTO_URL_EXPIRES_MINUTES = 15;

    protected $fillable = [
        'client_id',
        'name',
        'breed',
        'sex',
        'birth_date',
        'note',
        'photo_path',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /**
     * 会員
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * 計測値（D-0800）
     *
     * 並び順は計測日時の降順（新しい順）。トレーニー詳細（S-0309）で
     * この並び順のまま表示する。
     */
    public function measurements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TraineeMeasurement::class)
            ->orderBy('measured_date', 'desc')
            ->orderBy('measured_time', 'desc');
    }

    /**
     * 最終更新者（トレーナー）
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'updated_by');
    }

    /**
     * 性別の日本語表示
     */
    public function getSexLabelAttribute(): ?string
    {
        return $this->sex ? (self::sexLabels()[$this->sex] ?? $this->sex) : null;
    }

    /**
     * 誕生日から算出した年齢（年単位・切り捨て）。
     * 誕生日が未登録なら null を返す。
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    /**
     * 最新の計測値（計測日時の降順で先頭）。計測値が 0 件なら null。
     *
     * `measurements()` リレーションが計測日時降順で並ぶ既定を利用し、
     * `first()` で先頭 1 件を取り出す。会員詳細（S-0305）のトレーニーカードで
     * 「最終計測」の表示に使う。呼び出し側は `trainees.measurements` を
     * eager load しておくこと（`ClientController::show()` 参照）。
     * 詳細は screen-design.md S-0305 セクション3「最終計測」参照。
     */
    public function getLatestMeasurementAttribute(): ?TraineeMeasurement
    {
        return $this->measurements->first();
    }

    /**
     * トレーニー写真の presigned 表示 URL を返す。
     *
     * `photo_path` が NULL のとき（写真未登録）は null。
     * それ以外は media ディスクの temporaryUrl で署名付き URL を発行する。
     * 期限は呼び出し側で有効期限をこの場で決めるより、モデルに固定値
     * （PHOTO_URL_EXPIRES_MINUTES）を持たせて Blade で毎回書かなくても済むようにする。
     * 既存の MediaRecord::temporaryThumbnailUrl(DateTimeInterface) は呼び出し側で
     * 期限を渡す設計だが、あちらは一覧で複数メディアの期限を揃える運用のため。
     * こちらはトレーニーごとに 1 枚で運用差が生じないため、モデル側で完結させる。
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }
        return Storage::disk(self::STORAGE_DISK)
            ->temporaryUrl($this->photo_path, now()->addMinutes(self::PHOTO_URL_EXPIRES_MINUTES));
    }
}
