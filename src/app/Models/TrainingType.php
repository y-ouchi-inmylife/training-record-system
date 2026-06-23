<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * トレーニング内容マスタモデル
 */
class TrainingType extends Model
{
    // 段階3でテーブル名を training_types にリネームするまでの橋渡し（クラス名が先行）
    protected $table = 'consultation_types';

    protected $fillable = [
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function counselingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }
}
