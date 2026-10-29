<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'assessed_at', 'usual_habits', 'subjective_quality', 'nocturnal_pain', 'anxiety',
    'light_exposure', 'noise_level', 'interruptions_count', 'night_medication', 'ventilation_interference',
    'procedures_at_night', 'day_night_orientation', 'interventions', 'barriers',
])]
class IcuSleepAssessment extends Model
{
    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
            'nocturnal_pain' => 'boolean',
            'anxiety' => 'boolean',
            'ventilation_interference' => 'boolean',
            'procedures_at_night' => 'boolean',
            'interventions' => 'array',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }
}
