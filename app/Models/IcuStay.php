<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable([
    'clinical_program_id', 'patient_id', 'site_id', 'icu_unit_id', 'assigned_auditor_id', 'created_by', 'updated_by',
    'case_number', 'case_sequence', 'bed_label', 'admission_number', 'admission_diagnosis', 'admission_source',
    'status', 'admission_at', 'mechanical_ventilation', 'ventilation_start_at', 'extubation_at',
    'unplanned_extubation', 'reintubation_at', 'tracheostomy_at', 'icu_discharge_at', 'icu_discharge_destination',
    'hospital_discharge_at', 'death_at', 'outcome_state', 'icu_stay_days', 'hospital_stay_days',
    'is_valid', 'is_cancelled', 'exclusion_reason', 'field_status', 'completed_at',
])]
class IcuStay extends Model
{
    public const STATUSES = [
        'active' => 'Activo en UCI',
        'discharged_icu' => 'Egresado de UCI',
        'discharged_hospital' => 'Egresado del hospital',
        'deceased' => 'Fallecido',
        'cancelled' => 'Anulado',
    ];

    /**
     * Componentes del bundle que participan en el semáforo diario y en el cálculo
     * de cumplimiento por oportunidades. SAT/SBT solo aplican si hay ventilación
     * mecánica; el resto se considera de aplicación continua salvo que se marque
     * explícitamente "no aplica" en field_status.
     */
    public const BUNDLE_COMPONENTS = [
        'pain' => 'Dolor',
        'sedation' => 'Analgesia y sedación',
        'delirium' => 'Delirium',
        'mobility' => 'Movilidad',
        'sat' => 'SAT',
        'sbt' => 'SBT',
    ];

    public const COMPONENT_STATUS_OPTIONS = [
        'not_applicable' => 'No aplica',
        'not_performed' => 'No realizado',
        'unknown' => 'Desconocido',
    ];

    protected function casts(): array
    {
        return [
            'admission_at' => 'datetime',
            'mechanical_ventilation' => 'boolean',
            'ventilation_start_at' => 'datetime',
            'extubation_at' => 'datetime',
            'unplanned_extubation' => 'boolean',
            'reintubation_at' => 'datetime',
            'tracheostomy_at' => 'datetime',
            'icu_discharge_at' => 'datetime',
            'hospital_discharge_at' => 'datetime',
            'death_at' => 'datetime',
            'icu_stay_days' => 'decimal:2',
            'hospital_stay_days' => 'decimal:2',
            'is_valid' => 'boolean',
            'is_cancelled' => 'boolean',
            'field_status' => 'array',
            'completed_at' => 'datetime',
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

    public function icuUnit(): BelongsTo
    {
        return $this->belongsTo(IcuUnit::class);
    }

    public function assignedAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_auditor_id');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(IcuLiberationRound::class);
    }

    public function painAssessments(): HasMany
    {
        return $this->hasMany(IcuPainAssessment::class);
    }

    public function satTrials(): HasMany
    {
        return $this->hasMany(IcuSatTrial::class);
    }

    public function sbtTrials(): HasMany
    {
        return $this->hasMany(IcuSbtTrial::class);
    }

    public function sedationAssessments(): HasMany
    {
        return $this->hasMany(IcuSedationAssessment::class);
    }

    public function deliriumAssessments(): HasMany
    {
        return $this->hasMany(IcuDeliriumAssessment::class);
    }

    public function mobilitySessions(): HasMany
    {
        return $this->hasMany(IcuMobilitySession::class);
    }

    public function familyEngagements(): HasMany
    {
        return $this->hasMany(IcuFamilyEngagement::class);
    }

    public function sleepAssessments(): HasMany
    {
        return $this->hasMany(IcuSleepAssessment::class);
    }

    public function physicalRestraints(): HasMany
    {
        return $this->hasMany(IcuPhysicalRestraint::class);
    }

    public function deviceReviews(): HasMany
    {
        return $this->hasMany(IcuDeviceReview::class);
    }

    public function transferChecklist(): HasOne
    {
        return $this->hasOne(IcuTransferChecklist::class);
    }

    public function picsFollowups(): HasMany
    {
        return $this->hasMany(IcuPicsFollowup::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(IcuStayAudit::class);
    }

    public function infusionEvents(): HasMany
    {
        return $this->hasMany(InfusionEvent::class);
    }

    public function infusionClinicalAlerts(): HasMany
    {
        return $this->hasMany(InfusionClinicalAlert::class);
    }

    public function visScoreSnapshots(): HasMany
    {
        return $this->hasMany(VisScoreSnapshot::class);
    }

    public function weights(): HasMany
    {
        return $this->hasMany(IcuStayWeight::class);
    }

    public function componentOverride(string $component): ?string
    {
        return $this->field_status[$component] ?? null;
    }

    /**
     * Estado de cada componente aplicable para una fecha dada. Un componente marcado
     * "no aplica" en field_status se excluye de la exigencia (no es un incumplimiento);
     * SAT/SBT se excluyen estructuralmente si la estancia no está ventilada. Requiere
     * las relaciones de componentes precargadas para evitar N+1.
     *
     * @return array<string, array{label: string, applicable: bool, done: bool, override: ?string}>
     */
    public function bundleStatusForDate(Carbon $date): array
    {
        $checks = [
            'pain' => fn (): bool => $this->painAssessments->contains(fn ($a) => $a->assessed_at?->isSameDay($date)),
            'sedation' => fn (): bool => $this->sedationAssessments->contains(fn ($a) => $a->assessed_at?->isSameDay($date)),
            'delirium' => fn (): bool => $this->deliriumAssessments->contains(fn ($a) => $a->assessed_at?->isSameDay($date)),
            'mobility' => fn (): bool => $this->mobilitySessions->contains(fn ($a) => $a->performed_at?->isSameDay($date)),
            'sat' => fn (): bool => $this->satTrials->contains(fn ($a) => $a->screened_at?->isSameDay($date)),
            'sbt' => fn (): bool => $this->sbtTrials->contains(fn ($a) => $a->screened_at?->isSameDay($date)),
        ];

        $result = [];
        foreach (self::BUNDLE_COMPONENTS as $key => $label) {
            if (in_array($key, ['sat', 'sbt'], true) && ! $this->mechanical_ventilation) {
                continue;
            }

            $override = $this->componentOverride($key);
            $result[$key] = [
                'label' => $label,
                'applicable' => $override !== 'not_applicable',
                'done' => $override === 'not_applicable' ? true : (bool) $checks[$key](),
                'override' => $override,
            ];
        }

        return $result;
    }

    /**
     * Resumen de semáforo (verde/amarillo/rojo) para el censo y la ronda diaria.
     *
     * @return array{components: array, applicable_count: int, done_count: int, color: string}
     */
    public function bundleSummaryForDate(Carbon $date): array
    {
        $components = $this->bundleStatusForDate($date);
        $applicable = collect($components)->where('applicable', true);
        $done = $applicable->where('done', true);

        $color = match (true) {
            $applicable->isEmpty() => 'gray',
            $done->count() === 0 => 'red',
            $done->count() === $applicable->count() => 'green',
            default => 'yellow',
        };

        return [
            'components' => $components,
            'applicable_count' => $applicable->count(),
            'done_count' => $done->count(),
            'color' => $color,
        ];
    }
}
