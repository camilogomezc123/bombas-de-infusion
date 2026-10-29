<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'checkpoint', 'contact_method', 'contact_achieved', 'functional_capacity', 'mobility',
    'strength', 'fatigue', 'pain', 'sleep_quality', 'cognition_tool', 'cognition_result', 'anxiety_tool',
    'anxiety_result', 'depression_tool', 'depression_result', 'ptsd_tool', 'ptsd_result', 'medications_review',
    'readmission', 'return_to_work', 'quality_of_life_tool', 'quality_of_life_result', 'caregiver_burden_tool',
    'caregiver_burden_result', 'referred_to', 'notes', 'followed_up_at',
])]
class IcuPicsFollowup extends Model
{
    public const CHECKPOINTS = [
        '48_72h' => '48-72 horas post-egreso hospitalario',
        '7d' => '7 días',
        '30d' => '30 días',
        '3m' => '3 meses',
        '6m' => '6 meses',
        '12m' => '12 meses',
    ];

    protected function casts(): array
    {
        return [
            'contact_achieved' => 'boolean',
            'readmission' => 'boolean',
            'return_to_work' => 'boolean',
            'referred_to' => 'array',
            'followed_up_at' => 'datetime',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }
}
