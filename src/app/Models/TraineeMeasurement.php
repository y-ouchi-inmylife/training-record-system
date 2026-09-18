<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * トレーニー計測値モデル（D-0800）
 *
 * トレーニーごとの計測記録。現状は体重のみ。トレーニング記録（TrainingRecord）
 * とは独立したデータで、日付で結合することはあっても直接の紐付けは持たない。
 * 削除は物理削除、権限は一般トレーナーも可（設計書 requirements.md 6-16-7）。
 */
class TraineeMeasurement extends Model
{
    protected $fillable = [
        'trainee_id',
        'measured_date',
        'measured_time',
        'weight_kg',
        'note',
        'updated_by',
    ];

    /**
     * `measured_time` は TIME 型で HH:MM:SS 形式の文字列としてそのまま扱う。
     * 既存 `training_records.training_time` と同じ扱い（キャストは持たせず、
     * 表示側で `substr($v, 0, 5)` して分までを表示する）。
     */
    protected function casts(): array
    {
        return [
            'measured_date' => 'date',
            'weight_kg' => 'decimal:2',
        ];
    }

    /**
     * 親トレーニー
     */
    public function trainee(): BelongsTo
    {
        return $this->belongsTo(Trainee::class);
    }

    /**
     * 最終更新者（トレーナー）
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'updated_by');
    }
}
