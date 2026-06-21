<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * フェーズマスタモデル
 */
class Phase extends Model
{
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
        return $this->hasMany(CounselingRecord::class);
    }
}
