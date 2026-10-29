<?php

namespace App\Models;

use App\Enums\CaseStatus;
use App\Enums\ClinicalStage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'clinical_program_id', 'site_id', 'patient_id', 'icu_stay_id', 'assigned_auditor_id', 'created_by', 'updated_by',
    'case_number', 'case_sequence', 'status', 'month', 'enrollment_at', 'enrollment_source',
    'mechanical_ventilation_days', 'delirium_days', 'icu_los_days', 'sedation_deep_days',
    'age_at_admission', 'barthel_at_discharge', 'shock_or_sepsis', 'mrc_total', 'handgrip_kg',
    'risk_score', 'risk_level', 'risk_factors',
    'clinical_data', 'field_status', 'is_valid', 'is_cancelled', 'cancellation_reason',
    'assigned_at', 'analysis_started_at', 'auditor_finalized_at', 'completed_at', 'cancelled_at',
    'clinical_stage', 'uci_started_at', 'hospitalization_started_at',
    'discharge_confirmed_at', 'discharge_confirmed_by', 'followup_started_at', 'last_inactivity_alert_at',
])]
class PicsCase extends Model
{
    public const ENROLLMENT_SOURCES = [
        'icu_discharge' => 'Egreso de UCI',
        'hospital_discharge' => 'Egreso hospitalario',
        'referral' => 'Remisión externa',
        'other' => 'Otro',
    ];

    public const RISK_LEVELS = [
        'bajo' => 'Riesgo bajo',
        'medio' => 'Riesgo medio',
        'alto' => 'Riesgo alto',
    ];

    /** Umbral de fuerza de prensión (handgrip) por sexo, kg — bajo este valor cuenta como DAUCI. */
    private const HANDGRIP_THRESHOLD_FEMALE = 16.0;

    private const HANDGRIP_THRESHOLD_MALE = 27.0;

    /**
     * Campos clave usados para medir completitud del registro. Un campo vacío no
     * implica incumplimiento clínico — ver field_status para "no realizado/no aplica/desconocido".
     */
    public const KEY_TRACKING_FIELDS = [
        'enrollment_at' => 'Ingreso al programa',
        'icu_los_days' => 'Estancia UCI de origen',
    ];

    public const FIELD_STATUS_OPTIONS = [
        'not_performed' => 'No realizado',
        'not_applicable' => 'No aplica',
        'unknown' => 'Desconocido',
    ];

    protected function casts(): array
    {
        return [
            'status' => CaseStatus::class,
            'clinical_stage' => ClinicalStage::class,
            'uci_started_at' => 'datetime',
            'hospitalization_started_at' => 'datetime',
            'discharge_confirmed_at' => 'datetime',
            'followup_started_at' => 'datetime',
            'enrollment_at' => 'datetime',
            'mechanical_ventilation_days' => 'decimal:3',
            'delirium_days' => 'decimal:3',
            'icu_los_days' => 'decimal:3',
            'sedation_deep_days' => 'decimal:3',
            'barthel_at_discharge' => 'decimal:1',
            'shock_or_sepsis' => 'boolean',
            'handgrip_kg' => 'decimal:1',
            'risk_factors' => 'array',
            'clinical_data' => 'array',
            'field_status' => 'array',
            'is_valid' => 'boolean',
            'is_cancelled' => 'boolean',
            'assigned_at' => 'datetime',
            'analysis_started_at' => 'datetime',
            'auditor_finalized_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_inactivity_alert_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function icuStay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class);
    }

    public function assignedAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_auditor_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ClinicalAudit::class, 'auditable');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(PicsFollowup::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(PicsReferral::class);
    }

    public function diaryEntries(): HasMany
    {
        return $this->hasMany(DiaryEntry::class);
    }

    public function recoveryGoals(): HasMany
    {
        return $this->hasMany(RecoveryGoal::class);
    }

    public function caregiverAuthorizations(): HasMany
    {
        return $this->hasMany(CaregiverAuthorization::class);
    }

    public function recoveryPassport(): HasOne
    {
        return $this->hasOne(RecoveryPassport::class);
    }

    public function supportRequests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
    }

    public function dischargeConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discharge_confirmed_by');
    }

    public function clinicalStageLabel(): string
    {
        return $this->clinical_stage instanceof ClinicalStage
            ? $this->clinical_stage->label()
            : (string) $this->clinical_stage;
    }

    public function carePlan(): HasOne
    {
        return $this->hasOne(CarePlan::class);
    }

    public function caregiverJourneySteps(): HasMany
    {
        return $this->hasMany(CaregiverJourneyStep::class);
    }

    public function dischargeReadinessCheck(): HasOne
    {
        return $this->hasOne(DischargeReadinessCheck::class);
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(PicsAgendaItem::class);
    }

    public function medicationReconciliation(): HasOne
    {
        return $this->hasOne(MedicationReconciliation::class);
    }

    public function homeMonitoringReadings(): HasMany
    {
        return $this->hasMany(HomeMonitoringReading::class);
    }

    public function educationAssignments(): HasMany
    {
        return $this->hasMany(EducationAssignment::class);
    }

    public function personalReminders(): HasMany
    {
        return $this->hasMany(PersonalReminder::class);
    }

    /**
     * Algoritmo de riesgo PICS de 7 factores, portado tal cual del proyecto "Panel de
     * control" (PicsController::computarRiesgo()): misma ponderación, mismos cortes.
     * No se recalcula automáticamente en cada guardado — los datos de riesgo (Barthel,
     * MRC, handgrip) suelen completarse en etapas; se dispara con la acción explícita
     * "Recalcular riesgo".
     *
     * @return array{score: int, level: string, factors: array<int, string>}
     */
    public function calculateRisk(): array
    {
        $score = 0;
        $factors = [];

        $losDays = $this->icu_los_days !== null ? (float) $this->icu_los_days : null;
        if ($losDays !== null) {
            if ($losDays >= 14) {
                $score += 2;
                $factors[] = "Estancia UCI ≥ 14 días ({$losDays} días): +2";
            } elseif ($losDays >= 7) {
                $score += 1;
                $factors[] = "Estancia UCI 7-13 días ({$losDays} días): +1";
            } elseif ($losDays > 5) {
                $score += 1;
                $factors[] = "Estancia UCI > 5 días ({$losDays} días): +1";
            }
        }

        $vmDays = $this->mechanical_ventilation_days !== null ? (float) $this->mechanical_ventilation_days : null;
        if ($vmDays !== null) {
            if ($vmDays >= 7) {
                $score += 3;
                $factors[] = "Ventilación mecánica ≥ 7 días ({$vmDays} días): +3";
            } elseif ($vmDays > 2) {
                $score += 2;
                $factors[] = "Ventilación mecánica > 2 días ({$vmDays} días): +2";
            } elseif ($vmDays >= 1) {
                $score += 1;
                $factors[] = "Ventilación mecánica 1-2 días ({$vmDays} días): +1";
            }
        }

        $deliriumDays = $this->delirium_days !== null ? (float) $this->delirium_days : null;
        if ($deliriumDays !== null) {
            if ($deliriumDays >= 4) {
                $score += 3;
                $factors[] = "Delirium ≥ 4 días ({$deliriumDays} días): +3";
            } elseif ($deliriumDays >= 2) {
                $score += 2;
                $factors[] = "Delirium 2-3 días ({$deliriumDays} días): +2";
            } elseif ($deliriumDays >= 1) {
                $score += 1;
                $factors[] = "Delirium 1 día: +1";
            }
        }

        if ($this->age_at_admission !== null && $this->age_at_admission >= 65) {
            $score += 2;
            $factors[] = "Edad ≥ 65 años ({$this->age_at_admission} años): +2";
        }

        if ($this->barthel_at_discharge !== null && (float) $this->barthel_at_discharge < 100) {
            $score += 1;
            $factors[] = "Barthel < 100 (último: {$this->barthel_at_discharge}): +1";
        }

        if ($this->shock_or_sepsis === true) {
            $score += 2;
            $factors[] = 'Choque / sepsis en el diagnóstico: +2';
        }

        $mrcAltered = $this->mrc_total !== null && $this->mrc_total < 48;
        $handgripThreshold = $this->patient?->sex === 'F' ? self::HANDGRIP_THRESHOLD_FEMALE : self::HANDGRIP_THRESHOLD_MALE;
        $handgripAltered = $this->handgrip_kg !== null && (float) $this->handgrip_kg > 0 && (float) $this->handgrip_kg < $handgripThreshold;

        if ($mrcAltered || $handgripAltered) {
            $score += 2;
            $detail = collect([
                $mrcAltered ? 'MRC < 48' : null,
                $handgripAltered ? "Handgrip < {$handgripThreshold} kg" : null,
            ])->filter()->implode(' · ');
            $factors[] = "DAUCI positivo ({$detail}): +2";
        }

        $level = match (true) {
            $score > 2 => 'alto',
            $score > 1 => 'medio',
            default => 'bajo',
        };

        return ['score' => $score, 'level' => $level, 'factors' => $factors];
    }

    /**
     * Calcula y persiste el riesgo (usado por la acción "Recalcular riesgo").
     */
    public function recalculateRisk(): static
    {
        $result = $this->calculateRisk();
        $this->risk_score = $result['score'];
        $this->risk_level = $result['level'];
        $this->risk_factors = $result['factors'];

        return $this;
    }

    public function riskLevelLabel(): ?string
    {
        return $this->risk_level ? (self::RISK_LEVELS[$this->risk_level] ?? $this->risk_level) : null;
    }

    public function riskLevelColor(): string
    {
        return match ($this->risk_level) {
            'alto' => 'danger',
            'medio' => 'warning',
            'bajo' => 'success',
            default => 'gray',
        };
    }

    /**
     * Completitud de los campos clave. Los campos marcados "no aplica" se excluyen del
     * denominador; no usa ni afecta los indicadores institucionales — es calidad del dato.
     *
     * @return array{percentage: float, items: array<int, array{field: string, label: string, status: string}>}
     */
    public function completenessSummary(): array
    {
        $overrides = $this->field_status ?? [];

        $items = collect(self::KEY_TRACKING_FIELDS)->map(function (string $label, string $field) use ($overrides): array {
            if (filled($this->{$field})) {
                return ['field' => $field, 'label' => $label, 'status' => 'filled'];
            }

            $override = $overrides[$field] ?? null;

            return ['field' => $field, 'label' => $label, 'status' => $override ?? 'pending'];
        })->values();

        $applicable = $items->reject(fn (array $item): bool => $item['status'] === 'not_applicable');
        $filled = $applicable->where('status', 'filled');

        $percentage = $applicable->isEmpty() ? 100.0 : round(($filled->count() / $applicable->count()) * 100, 1);

        return ['percentage' => $percentage, 'items' => $items->all()];
    }
}
