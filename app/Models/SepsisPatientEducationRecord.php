<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sepsis_case_id', 'informed_person', 'relationship_to_patient', 'occurred_at',
    'diagnosis_explained', 'treatment_explained', 'risks_explained', 'procedures_explained',
    'prognosis_explained', 'goals_of_care', 'preferences', 'comprehension_barriers',
    'material_provided', 'teach_back_done', 'discharge_readiness', 'warning_signs',
    'post_sepsis_follow_up', 'recorded_by',
])]
class SepsisPatientEducationRecord extends Model
{
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'diagnosis_explained' => 'boolean',
            'treatment_explained' => 'boolean',
            'risks_explained' => 'boolean',
            'procedures_explained' => 'boolean',
            'prognosis_explained' => 'boolean',
            'teach_back_done' => 'boolean',
            'discharge_readiness' => 'boolean',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SepsisCase::class, 'sepsis_case_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
