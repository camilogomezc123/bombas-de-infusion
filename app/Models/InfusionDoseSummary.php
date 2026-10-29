<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'drug_infusion_profile_id', 'summary_date', 'cumulative_dose', 'dose_unit',
    'max_rate_seen', 'max_rate_unit', 'computed_at',
])]
class InfusionDoseSummary extends Model
{
    protected function casts(): array
    {
        return [
            'summary_date' => 'date',
            'cumulative_dose' => 'decimal:4',
            'max_rate_seen' => 'decimal:4',
            'computed_at' => 'datetime',
        ];
    }

    public function icuStay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class);
    }

    public function drugInfusionProfile(): BelongsTo
    {
        return $this->belongsTo(DrugInfusionProfile::class);
    }
}
