<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sepsis_case_id', 'assessed_at', 'map', 'diastolic_pressure', 'heart_rate',
    'capillary_refill_seconds', 'lactate', 'urine_output_ml', 'fluid_intake_ml', 'fluid_output_ml',
    'mental_status',
    'cold_mottled_skin', 'congestion', 'volume_response_method', 'volume_response_result',
    'phenotype', 'ultrasound_findings', 'vti', 'lv_function', 'rv_function', 'cvp',
    'vasopressors', 'inotropes', 'conduct', 'next_assessment_at', 'assessed_by',
])]
class SepsisHemodynamicAssessment extends Model
{
    public const PHENOTYPES = [
        'volume_responder' => 'Respondedor a volumen',
        'vasoplegic' => 'Vasopléjico',
        'low_output' => 'Bajo gasto o disfunción cardiaca',
        'congestion' => 'Congestión o falla ventricular derecha',
    ];

    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
            'map' => 'decimal:2',
            'diastolic_pressure' => 'decimal:2',
            'capillary_refill_seconds' => 'decimal:2',
            'lactate' => 'decimal:2',
            'urine_output_ml' => 'decimal:2',
            'fluid_intake_ml' => 'decimal:1',
            'fluid_output_ml' => 'decimal:1',
            'cold_mottled_skin' => 'boolean',
            'congestion' => 'boolean',
            'vti' => 'decimal:2',
            'cvp' => 'decimal:2',
            'next_assessment_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SepsisCase::class, 'sepsis_case_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function fluidBalanceMl(): ?float
    {
        if ($this->fluid_intake_ml === null && $this->fluid_output_ml === null) {
            return null;
        }

        return (float) ($this->fluid_intake_ml ?? 0) - (float) ($this->fluid_output_ml ?? 0);
    }
}
