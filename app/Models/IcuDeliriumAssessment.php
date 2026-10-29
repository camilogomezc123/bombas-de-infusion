<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'assessed_at', 'tool', 'assessable', 'coma_state', 'result', 'subtype',
    'precipitating_factors', 'associated_medications', 'interventions', 'reassessment_at',
])]
class IcuDeliriumAssessment extends Model
{
    public const TOOLS = [
        'cam_icu' => 'CAM-ICU',
        'icdsc' => 'ICDSC',
        'institutional' => 'Herramienta institucional aprobada',
    ];

    public const RESULTS = [
        'positive' => 'Positivo',
        'negative' => 'Negativo',
        'unable' => 'No evaluable',
    ];

    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
            'assessable' => 'boolean',
            'interventions' => 'array',
            'reassessment_at' => 'datetime',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }
}
