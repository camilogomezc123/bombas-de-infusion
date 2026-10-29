<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'screened_at', 'eligible', 'exclusion_reason', 'started_at', 'result',
    'failure_reason', 'subsequent_action', 'adverse_event',
])]
class IcuSatTrial extends Model
{
    public const EXCLUSION_REASONS = [
        'seizure_sedation' => 'Sedación por convulsiones',
        'active_withdrawal' => 'Abstinencia activa',
        'neuromuscular_blockade' => 'Bloqueo neuromuscular',
        'intracranial_hypertension' => 'Hipertensión intracraneal',
        'severe_agitation' => 'Agitación grave',
        'active_ischemia' => 'Isquemia activa',
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
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }
}
