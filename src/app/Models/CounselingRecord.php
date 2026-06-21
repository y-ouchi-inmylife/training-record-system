<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * トレーニング記録モデル
 */
class CounselingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'consultation_date', 'consultation_time',
        'is_intake', 'is_followup',
        'consultation_type_id', 'consultation_detail',
        'counselor1_id', 'counselor2_id',
        'record_content', 'impression',
        'phase_id', 'attendance',
        'consultation_format', 'consultation_format_detail',
        // 最終更新者
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'consultation_date' => 'date',
            'is_intake' => 'boolean',
            'is_followup' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function consultationType(): BelongsTo
    {
        return $this->belongsTo(ConsultationType::class);
    }

    public function counselor1(): BelongsTo
    {
        return $this->belongsTo(Counselor::class, 'counselor1_id');
    }

    public function counselor2(): BelongsTo
    {
        return $this->belongsTo(Counselor::class, 'counselor2_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CounselingParticipant::class);
    }

    /**
     * 最終更新者（トレーナー）
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Counselor::class, 'updated_by');
    }
}
