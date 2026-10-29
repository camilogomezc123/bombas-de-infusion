<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acs_case_id', 'medication_group', 'medication_name', 'ordered_at', 'administered_at', 'documented_dose', 'indicated', 'at_discharge', 'contraindication_or_omission_reason', 'discharge_plan'])]
class AcsMedicationRecord extends Model
{
    protected function casts(): array
    {
        return ['ordered_at' => 'datetime', 'administered_at' => 'datetime', 'indicated' => 'boolean', 'at_discharge' => 'boolean'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AcsCase::class, 'acs_case_id');
    }
}
