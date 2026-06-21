<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * トレーニング参加者モデル
 */
class CounselingParticipant extends Model
{
    /** updated_atを持たない */
    public $timestamps = false;

    protected $fillable = [
        'counseling_record_id',
        'participant_type',
        'participant_detail',
    ];

    public function counselingRecord(): BelongsTo
    {
        return $this->belongsTo(CounselingRecord::class);
    }
}
