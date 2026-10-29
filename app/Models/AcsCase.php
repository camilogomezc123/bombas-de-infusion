<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'clinical_program_id', 'patient_id', 'site_id', 'assigned_auditor_id', 'created_by', 'updated_by',
    'case_number', 'case_sequence', 'admission_number', 'status', 'diagnosis', 'acs_type', 'infarction_type',
    'entry_route', 'origin_service', 'referral_institution', 'insurer', 'municipality', 'presentation_type',
    'killip_class', 'grace_score', 'grace_risk_category', 'grace_assessed_at',
    'social_barriers', 'special_populations', 'symptom_onset_at', 'first_medical_contact_at',
    'admission_at', 'ecg_performed_at', 'ecg_interpreted_at', 'diagnosis_at', 'code_activated_at',
    'cath_lab_activated_at', 'cath_lab_arrival_at', 'first_device_at', 'fibrinolysis_at', 'angiography_at',
    'discharged_at', 'death_at', 'cardiac_arrest', 'cardiogenic_shock', 'eligible_for_reperfusion',
    'reperfusion_strategy', 'no_reperfusion_reason', 'nste_strategy', 'conservative_reason', 'icu_admission',
    'icu_stay_days', 'hospital_stay_days', 'outcome', 'readmission_30d', 'reinfarction_30d', 'mace_30d',
    'is_valid', 'is_cancelled', 'exclusion_reason', 'completed_at',
])]
class AcsCase extends Model
{
    public const TYPES = [
        'stemi' => 'STEMI',
        'nstemi' => 'NSTEMI',
        'nste_acs' => 'NSTE-ACS',
        'unstable_angina' => 'Angina inestable',
        'alternative' => 'Diagnóstico alternativo',
    ];

    protected function casts(): array
    {
        return [
            'special_populations' => 'array',
            'grace_score' => 'integer', 'grace_assessed_at' => 'datetime',
            'symptom_onset_at' => 'datetime', 'first_medical_contact_at' => 'datetime',
            'admission_at' => 'datetime', 'ecg_performed_at' => 'datetime', 'ecg_interpreted_at' => 'datetime',
            'diagnosis_at' => 'datetime', 'code_activated_at' => 'datetime', 'cath_lab_activated_at' => 'datetime',
            'cath_lab_arrival_at' => 'datetime', 'first_device_at' => 'datetime', 'fibrinolysis_at' => 'datetime',
            'angiography_at' => 'datetime', 'discharged_at' => 'datetime', 'death_at' => 'datetime',
            'completed_at' => 'datetime', 'cardiac_arrest' => 'boolean', 'cardiogenic_shock' => 'boolean',
            'eligible_for_reperfusion' => 'boolean', 'icu_admission' => 'boolean', 'readmission_30d' => 'boolean',
            'reinfarction_30d' => 'boolean', 'mace_30d' => 'boolean', 'is_valid' => 'boolean',
            'is_cancelled' => 'boolean',
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

    public function ecgRecords(): HasMany
    {
        return $this->hasMany(AcsEcgRecord::class);
    }

    public function troponinRecords(): HasMany
    {
        return $this->hasMany(AcsTroponinRecord::class);
    }

    public function pciProcedures(): HasMany
    {
        return $this->hasMany(AcsPciProcedure::class);
    }

    public function medicationRecords(): HasMany
    {
        return $this->hasMany(AcsMedicationRecord::class);
    }

    public function complications(): HasMany
    {
        return $this->hasMany(AcsComplication::class);
    }

    public function dischargePlan(): HasOne
    {
        return $this->hasOne(AcsDischargePlan::class);
    }

    public function rehabilitationReferrals(): HasMany
    {
        return $this->hasMany(AcsRehabilitationReferral::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(AcsFollowup::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AcsCaseAudit::class);
    }

    public function minutesBetween(?string $from, ?string $to): ?int
    {
        if (! $from || ! $to || ! $this->{$from} || ! $this->{$to}) {
            return null;
        }

        return (int) $this->{$from}->diffInMinutes($this->{$to});
    }
}
