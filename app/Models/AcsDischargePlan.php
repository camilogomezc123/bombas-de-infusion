<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acs_case_id', 'final_diagnosis', 'coronary_anatomy', 'ejection_fraction', 'medication_reconciliation', 'dapt_plan', 'high_intensity_statin', 'verbal_education', 'written_education', 'teach_back', 'warning_signs', 'smoking_plan', 'cardiology_appointment', 'primary_care_appointment', 'rehabilitation_referral', 'lipid_profile_plan', 'medication_access_verified', 'barriers_and_plan'])]
class AcsDischargePlan extends Model
{
    protected function casts(): array
    {
        return ['ejection_fraction' => 'decimal:2', 'medication_reconciliation' => 'boolean', 'dapt_plan' => 'boolean', 'high_intensity_statin' => 'boolean', 'verbal_education' => 'boolean', 'written_education' => 'boolean', 'teach_back' => 'boolean', 'warning_signs' => 'boolean', 'smoking_plan' => 'boolean', 'cardiology_appointment' => 'boolean', 'primary_care_appointment' => 'boolean', 'rehabilitation_referral' => 'boolean', 'lipid_profile_plan' => 'boolean', 'medication_access_verified' => 'boolean'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AcsCase::class, 'acs_case_id');
    }
}
