<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sepsis_case_id', 'origin_service', 'destination_service', 'transitioned_at',
    'handing_clinician', 'receiving_clinician', 'clinical_status', 'pending_items',
    'bundle_pending', 'active_antimicrobials', 'source_control_pending', 'icu_needed',
    'discharge', 'post_sepsis_plan',
])]
class SepsisCareTransition extends Model
{
    protected function casts(): array
    {
        return [
            'transitioned_at' => 'datetime',
            'bundle_pending' => 'boolean',
            'source_control_pending' => 'boolean',
            'icu_needed' => 'boolean',
            'discharge' => 'boolean',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SepsisCase::class, 'sepsis_case_id');
    }
}
