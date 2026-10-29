<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sepsis_case_id', 'checkpoint', 'scheduled_on', 'contacted_at', 'contact_achieved', 'clinical_status',
    'functional_status', 'mobility', 'cognitive_status', 'cognitive_instrument', 'emotional_status', 'nutrition_status',
    'medications_reviewed', 'rehabilitation_needed', 'social_needs', 'readmission', 'readmission_reason', 'reconsultation',
    'adherence', 'barriers', 'mortality', 'needs_intervention', 'recurrence_risk', 'responsible_user_id',
])]
class SepsisPostsepsisFollowup extends Model
{
    public const CHECKPOINTS = [
        '48_72h' => '48-72 horas',
        '7d' => '7 días',
        '15d' => '15 días',
        '30d' => '30 días',
        '90d' => '90 días',
    ];

    public const READMISSION_REASONS = [
        'sepsis_recurrence' => 'Recurrencia de sepsis',
        'infection_unrelated' => 'Infección no relacionada',
        'complication' => 'Complicación del episodio inicial',
        'comorbidity' => 'Descompensación de comorbilidad',
        'other' => 'Otro',
    ];

    public const COGNITIVE_INSTRUMENTS = [
        'moca' => 'MoCA',
        'iqcode' => 'IQCODE',
        'mmse' => 'MMSE',
        'clinical_impression' => 'Impresión clínica (sin instrumento validado)',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_on' => 'date',
            'contacted_at' => 'datetime',
            'contact_achieved' => 'boolean',
            'medications_reviewed' => 'boolean',
            'rehabilitation_needed' => 'boolean',
            'readmission' => 'boolean',
            'reconsultation' => 'boolean',
            'mortality' => 'boolean',
            'needs_intervention' => 'boolean',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SepsisCase::class, 'sepsis_case_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function checkpointLabel(): string
    {
        return self::CHECKPOINTS[$this->checkpoint] ?? $this->checkpoint;
    }
}
