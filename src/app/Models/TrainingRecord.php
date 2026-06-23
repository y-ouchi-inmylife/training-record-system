<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * トレーニング記録モデル
 */
class TrainingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'training_date', 'training_time',
        'training_type_id', 'training_detail',
        'trainer1_id', 'trainer2_id',
        'record_content', 'impression',
        'phase_id',
        // 最終更新者
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'training_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function trainingType(): BelongsTo
    {
        return $this->belongsTo(TrainingType::class);
    }

    public function trainer1(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer1_id');
    }

    public function trainer2(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer2_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * 最終更新者（トレーナー）
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'updated_by');
    }
}
