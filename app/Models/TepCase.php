<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'clinical_program_id', 'patient_id', 'site_id', 'assigned_auditor_id', 'created_by', 'updated_by',
    'case_number', 'case_sequence', 'admission_number', 'status', 'presentation', 'entry_route', 'origin_service',
    'tep_suspected', 'tep_confirmed', 'incidental_tep', 'symptom_onset_at', 'admission_at', 'diagnosis_at',
    'pretest_method', 'pretest_result', 'pretest_score', 'd_dimer_indicated', 'd_dimer_value', 'd_dimer_unit',
    'diagnostic_imaging', 'imaging_result', 'imaging_ordered_at', 'imaging_completed_at', 'aha_category',
    'aha_subcategory', 'category_documented_at', 'category_justification', 'pesi_score', 'spesi_high_risk',
    'hestia_positive', 'rv_dysfunction', 'troponin_positive', 'hypotension', 'shock', 'cardiac_arrest',
    'pert_required', 'pert_activated_at', 'pert_not_activated_reason', 'anticoagulation_ordered_at',
    'anticoagulation_started_at', 'anticoagulation_type', 'anticoagulation_contraindication', 'disposition',
    'icu_admission', 'icu_stay_days', 'hospital_stay_days', 'discharged_at', 'death_at', 'major_bleeding',
    'recurrence_30d', 'recurrence_90d', 'readmission_30d', 'is_valid', 'is_cancelled', 'exclusion_reason',
    'completed_at',
])]
class TepCase extends Model
{
    public const CATEGORIES = [
        'A' => 'A',
        'B' => 'B',
        'C' => 'C',
        'D' => 'D',
        'E' => 'E',
    ];

    protected function casts(): array
    {
        return [
            'tep_suspected' => 'boolean', 'tep_confirmed' => 'boolean', 'incidental_tep' => 'boolean',
            'symptom_onset_at' => 'datetime', 'admission_at' => 'datetime', 'diagnosis_at' => 'datetime',
            'd_dimer_indicated' => 'boolean', 'imaging_ordered_at' => 'datetime', 'imaging_completed_at' => 'datetime',
            'category_documented_at' => 'datetime', 'spesi_high_risk' => 'boolean', 'hestia_positive' => 'boolean',
            'rv_dysfunction' => 'boolean', 'troponin_positive' => 'boolean', 'hypotension' => 'boolean',
            'shock' => 'boolean', 'cardiac_arrest' => 'boolean', 'pert_required' => 'boolean',
            'pert_activated_at' => 'datetime', 'anticoagulation_ordered_at' => 'datetime',
            'anticoagulation_started_at' => 'datetime', 'icu_admission' => 'boolean', 'discharged_at' => 'datetime',
            'death_at' => 'datetime', 'major_bleeding' => 'boolean', 'recurrence_30d' => 'boolean',
            'recurrence_90d' => 'boolean', 'readmission_30d' => 'boolean', 'is_valid' => 'boolean',
            'is_cancelled' => 'boolean', 'completed_at' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function assignedAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_auditor_id');
    }

    public function pertActivations(): HasMany
    {
        return $this->hasMany(TepPertActivation::class);
    }

    public function imagingStudies(): HasMany
    {
        return $this->hasMany(TepImagingStudy::class);
    }

    public function anticoagulationEpisodes(): HasMany
    {
        return $this->hasMany(TepAnticoagulationEpisode::class);
    }

    public function advancedTherapies(): HasMany
    {
        return $this->hasMany(TepAdvancedTherapy::class);
    }

    public function dischargePlan(): HasOne
    {
        return $this->hasOne(TepDischargePlan::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(TepFollowup::class);
    }

    public function ctepdEvaluations(): HasMany
    {
        return $this->hasMany(TepCtepdEvaluation::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(TepCaseAudit::class);
    }
}
