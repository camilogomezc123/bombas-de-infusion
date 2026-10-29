<?php

namespace App\Models;

use App\Enums\CaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'patient_id', 'assigned_auditor_id', 'created_by', 'updated_by', 'case_number',
    'case_sequence', 'admission_number', 'resq_code', 'status', 'month', 'eapb',
    'health_regime', 'stroke_type', 'arrival_at', 'last_known_well_at', 'imaging_at',
    'thrombolysis_at', 'groin_puncture_at', 'revascularization_at', 'speech_therapy_at',
    'physiotherapy_at', 'discharged_at', 'thrombolysed', 'thrombectomy', 'deceased',
    'discharge_destination', 'hemorrhagic_transformation', 'is_recurrence', 'is_cancelled',
    'cancellation_reason', 'clinical_data', 'assigned_at', 'analysis_started_at',
    'auditor_finalized_at', 'completed_at', 'cancelled_at',
])]
class AcvCase extends Model
{
    protected function casts(): array
    {
        return [
            'status' => CaseStatus::class,
            'arrival_at' => 'datetime',
            'last_known_well_at' => 'datetime',
            'imaging_at' => 'datetime',
            'thrombolysis_at' => 'datetime',
            'groin_puncture_at' => 'datetime',
            'revascularization_at' => 'datetime',
            'speech_therapy_at' => 'datetime',
            'physiotherapy_at' => 'datetime',
            'discharged_at' => 'datetime',
            'assigned_at' => 'datetime',
            'analysis_started_at' => 'datetime',
            'auditor_finalized_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'thrombolysed' => 'boolean',
            'thrombectomy' => 'boolean',
            'deceased' => 'boolean',
            'hemorrhagic_transformation' => 'boolean',
            'is_recurrence' => 'boolean',
            'is_cancelled' => 'boolean',
            'clinical_data' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function assignedAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_auditor_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CaseComment::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(CaseAudit::class);
    }

    public function followup(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CaseFollowup::class);
    }

    public function isPendingAnalysis(): bool
    {
        return ! $this->is_cancelled
            && $this->discharged_at !== null
            && in_array($this->status, CaseStatus::pendingAnalysis(), true);
    }
}
