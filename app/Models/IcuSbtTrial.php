<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'screened_at', 'eligible', 'exclusion_reason', 'initial_parameters', 'modality',
    'started_at', 'duration_minutes', 'result', 'failure_reason', 'extubation_assessed', 'adverse_event',
])]
class IcuSbtTrial extends Model
{
    public const EXCLUSION_REASONS = [
        'respiratory_instability' => 'Inestabilidad respiratoria',
        'cardiovascular_instability' => 'Inestabilidad cardiovascular',
        'neurologic_impairment' => 'Alteración neurológica',
        'high_ventilatory_requirements' => 'Requerimientos ventilatorios elevados',
        'other' => 'Otra',
    ];

    public const RESULTS = [
        'success' => 'Exitoso',
        'fail' => 'Fallido',
        'not_done' => 'No realizado',
    ];

    protected function casts(): array
    {
        return [
            'screened_at' => 'datetime',
            'eligible' => 'boolean',
            'started_at' => 'datetime',
            'extubation_assessed' => 'boolean',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }
}
