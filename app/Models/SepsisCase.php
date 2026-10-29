<?php

namespace App\Models;

use App\Enums\CaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'clinical_program_id', 'site_id', 'patient_id', 'assigned_auditor_id', 'created_by', 'updated_by', 'case_number',
    'case_sequence', 'admission_number', 'origin_service', 'acquisition_type', 'special_population',
    'goals_of_care_limitation', 'goals_of_care_notes',
    'caregiver_name', 'caregiver_relationship', 'caregiver_identified_at',
    'status', 'month', 'registered_on',
    'activation_at', 'time_zero_validated_at', 'time_zero_validated_by',
    'antibiotic_at', 'fluids_at', 'drainage_at', 'admission_at',
    'discharged_at', 'uci_transfer_at', 'death_at', 'er_stay_days', 'clinic_stay_days',
    'uci_stay_days', 'culture_taken', 'culture_before_ab', 'ab_compliance', 'ab_adjusted',
    'culture_at', 'lactate_at', 'map_goal_met', 'antibiotics_used',
    'code_activated', 'septic_shock', 'uci', 'deceased', 'infection_focus', 'outcome_state', 'total_cost',
    'is_valid', 'is_recurrence', 'is_cancelled', 'cancellation_reason', 'clinical_data', 'field_status',
    'assigned_at', 'analysis_started_at', 'auditor_finalized_at', 'completed_at', 'cancelled_at',
])]
class SepsisCase extends Model
{
    /**
     * Campos clave de la ruta clínica usados para medir completitud del registro.
     * Un campo vacío no implica incumplimiento clínico: puede estar pendiente de
     * diligenciar, marcado explícitamente como no realizado, no aplicable, o desconocido
     * (ver field_status). Esta lista es la única fuente para el cálculo de completitud,
     * tanto por caso como en el agregado de SepsisExecutiveSummaryService.
     */
    public const KEY_TRACKING_FIELDS = [
        'admission_at' => 'Ingreso',
        'activation_at' => 'Activación / tiempo cero',
        'antibiotic_at' => 'Administración de antibiótico',
        'lactate_at' => 'Interpretación del lactato',
        'culture_at' => 'Toma de cultivos',
        'discharged_at' => 'Egreso',
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
            'registered_on' => 'date',
            'special_population' => 'array',
            'goals_of_care_limitation' => 'boolean',
            'caregiver_identified_at' => 'datetime',
            'activation_at' => 'datetime',
            'time_zero_validated_at' => 'datetime',
            'antibiotic_at' => 'datetime',
            'fluids_at' => 'datetime',
            'culture_at' => 'datetime',
            'lactate_at' => 'datetime',
            'map_goal_met' => 'boolean',
            'drainage_at' => 'datetime',
            'admission_at' => 'datetime',
            'discharged_at' => 'datetime',
            'uci_transfer_at' => 'datetime',
            'death_at' => 'datetime',
            'er_stay_days' => 'decimal:3',
            'clinic_stay_days' => 'decimal:3',
            'uci_stay_days' => 'decimal:3',
            'total_cost' => 'decimal:2',
            'culture_taken' => 'boolean',
            'culture_before_ab' => 'boolean',
            'ab_compliance' => 'boolean',
            'ab_adjusted' => 'boolean',
            'code_activated' => 'boolean',
            'septic_shock' => 'boolean',
            'uci' => 'boolean',
            'deceased' => 'boolean',
            'is_valid' => 'boolean',
            'is_recurrence' => 'boolean',
            'is_cancelled' => 'boolean',
            'clinical_data' => 'array',
            'field_status' => 'array',
            'assigned_at' => 'datetime',
            'analysis_started_at' => 'datetime',
            'auditor_finalized_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function timeZeroValidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'time_zero_validated_by');
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(SepsisScreening::class);
    }

    public function bundleTasks(): HasMany
    {
        return $this->hasMany(SepsisBundleTask::class);
    }

    public function hemodynamicAssessments(): HasMany
    {
        return $this->hasMany(SepsisHemodynamicAssessment::class);
    }

    public function cultures(): HasMany
    {
        return $this->hasMany(SepsisCulture::class);
    }

    public function antimicrobialAdministrations(): HasMany
    {
        return $this->hasMany(SepsisAntimicrobialAdministration::class);
    }

    public function sourceControlActions(): HasMany
    {
        return $this->hasMany(SepsisSourceControlAction::class);
    }

    public function careTransitions(): HasMany
    {
        return $this->hasMany(SepsisCareTransition::class);
    }

    public function educationRecords(): HasMany
    {
        return $this->hasMany(SepsisPatientEducationRecord::class);
    }

    public function postsepsisFollowups(): HasMany
    {
        return $this->hasMany(SepsisPostsepsisFollowup::class);
    }

    public function safetyEvents(): HasMany
    {
        return $this->hasMany(SepsisSafetyEvent::class);
    }

    /**
     * Completitud de los campos clave de la ruta clínica. Los campos marcados "no aplica"
     * se excluyen del denominador (no se penalizan); los demás estados vacíos (pendiente,
     * no realizado, desconocido) cuentan como incompletos. No usa ni afecta los 6
     * indicadores institucionales — es una métrica de calidad del dato, no clínica.
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
