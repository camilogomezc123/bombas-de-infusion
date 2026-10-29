<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinical_program_id', 'sepsis_case_id', 'sepsis_bundle_task_id', 'assessment_finding_id',
    'occurred_on', 'event_at', 'service', 'event_type', 'event_category', 'description',
    'harm_level', 'severity', 'immediate_action', 'analysis', 'improvement_action',
    'responsible_user_id', 'due_on', 'evidence_reference', 'effectiveness_verification',
    'status', 'closed_at',
])]
class SepsisSafetyEvent extends Model
{
    /**
     * Clasificación del evento, independiente de la severidad. Un evento centinela o
     * de alto daño debería enlazarse a un AssessmentFinding para un análisis causal
     * formal (5 porqués) en vez de solo el campo de análisis libre de este registro.
     */
    public const EVENT_CATEGORIES = [
        'adverse_event' => 'Evento adverso',
        'incident' => 'Incidente',
        'near_miss' => 'Cuasi falla',
        'sentinel_event' => 'Evento centinela',
    ];

    public const EVENT_TYPES = [
        'antibiotic_delay' => 'Retraso del antibiótico',
        'missing_cultures' => 'Omisión de hemocultivos',
        'lactate_delay' => 'Omisión o retraso del lactato',
        'medication_error' => 'Error de medicación',
        'communication_failure' => 'Falla de comunicación',
        'monitoring_issue' => 'Problema de monitorización',
        'late_transfer' => 'Traslado tardío',
        'late_source_control' => 'Control del foco tardío',
        'late_activation' => 'Activación tardía',
        'undetected_case' => 'Caso no detectado',
        'fluid_overload' => 'Sobrecarga de líquidos',
        'bundle_noncompliance' => 'Incumplimiento del bundle',
        'availability_failure' => 'Falla de disponibilidad',
    ];

    public const STATUSES = [
        'open' => 'Abierto',
        'analysis' => 'En análisis',
        'action' => 'Con acción de mejora',
        'verification' => 'Pendiente de verificación',
        'closed' => 'Cerrado',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'event_at' => 'datetime',
            'due_on' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SepsisCase::class, 'sepsis_case_id');
    }

    public function bundleTask(): BelongsTo
    {
        return $this->belongsTo(SepsisBundleTask::class, 'sepsis_bundle_task_id');
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(AssessmentFinding::class, 'assessment_finding_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
