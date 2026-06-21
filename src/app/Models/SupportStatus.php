<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 支援状態マスタモデル
 */
class SupportStatus extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'show_in_dashboard',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'show_in_dashboard' => 'boolean',
        ];
    }

    /**
     * この支援状態を持つクライアント
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }
}
