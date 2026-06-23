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

    // 段階3でテーブル名を training_records にリネームするまでの橋渡し（クラス名が先行）
    protected $table = 'counseling_records';

    protected $fillable = [
        'client_id', 'consultation_date', 'consultation_time',
        'consultation_type_id', 'consultation_detail',
        'counselor1_id', 'counselor2_id',
        'record_content', 'impression',
        'phase_id',
        // 最終更新者
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'consultation_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function consultationType(): BelongsTo
    {
        return $this->belongsTo(TrainingType::class);
    }

    public function counselor1(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'counselor1_id');
    }

    public function counselor2(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'counselor2_id');
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
