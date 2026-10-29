<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sepsis_case_id', 'occurred_at', 'service', 'clinician_name', 'suspected_infection',
    'probable_focus', 'news2_score', 'sofa_score',
    'sofa_respiratory', 'sofa_coagulation', 'sofa_liver', 'sofa_cardiovascular', 'sofa_cns', 'sofa_renal',
    'mental_status', 'urine_output_ml',
    'perfusion_status', 'vital_signs', 'result', 'conduct', 'recorded_by',
])]
class SepsisScreening extends Model
{
    /** Componentes del SOFA, en el orden estándar de reporte. */
    public const SOFA_COMPONENTS = [
        'sofa_respiratory' => 'Respiratorio',
        'sofa_coagulation' => 'Coagulación',
        'sofa_liver' => 'Hepático',
        'sofa_cardiovascular' => 'Cardiovascular',
        'sofa_cns' => 'Sistema nervioso central',
        'sofa_renal' => 'Renal',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'suspected_infection' => 'boolean',
            'urine_output_ml' => 'decimal:2',
            'vital_signs' => 'array',
        ];
    }

    /**
     * Suma de los 6 componentes cuando todos están registrados; de lo contrario null
     * (no se asume un valor parcial). No sustituye a sofa_score si este ya fue digitado
     * directamente; es solo una verificación de consistencia disponible para la interfaz.
     */
    public function sofaComponentsTotal(): ?int
    {
        $values = collect(array_keys(self::SOFA_COMPONENTS))->map(fn (string $field) => $this->{$field});

        return $values->contains(fn (?int $value): bool => $value === null) ? null : (int) $values->sum();
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SepsisCase::class, 'sepsis_case_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
