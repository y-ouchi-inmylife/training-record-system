<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ログイン試行記録モデル
 */
class LoginAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trainer_id',
        'login_id_input',
        'ip_address',
        'attempted_at',
        'success',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
            'success' => 'boolean',
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }
}
