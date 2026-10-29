<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sepsis_case_id', 'drug', 'dose', 'route', 'ordered_at', 'dispensed_at', 'administered_at',
    'renal_adjustment', 'allergy_checked', 'resistance_factors', 'proa_review', 'adjustment_or_deescalation',
])]
class SepsisAntimicrobialAdministration extends Model
{
    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'dispensed_at' => 'datetime',
            'administered_at' => 'datetime',
            'renal_adjustment' => 'boolean',
            'allergy_checked' => 'boolean',
            'proa_review' => 'boolean',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SepsisCase::class, 'sepsis_case_id');
    }
}
