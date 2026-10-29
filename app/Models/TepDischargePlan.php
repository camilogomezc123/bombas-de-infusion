<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tep_case_id', 'medication_reconciliation', 'anticoagulation_plan', 'duration_documented', 'bleeding_education', 'warning_signs', 'written_education', 'followup_scheduled', 'first_followup_on', 'barriers_and_plan'])]
class TepDischargePlan extends Model
{
    protected function casts(): array
    {
        return ['medication_reconciliation' => 'boolean', 'anticoagulation_plan' => 'boolean', 'duration_documented' => 'boolean', 'bleeding_education' => 'boolean', 'warning_signs' => 'boolean', 'written_education' => 'boolean', 'followup_scheduled' => 'boolean', 'first_followup_on' => 'date'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(TepCase::class, 'tep_case_id');
    }
}
