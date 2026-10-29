<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'assessed_at', 'scale', 'score', 'self_report_capable', 'location', 'pain_type',
    'related_procedure', 'intervention', 'non_pharmacologic_intervention', 'reassessed_at', 'score_after',
    'adverse_event',
])]
class IcuPainAssessment extends Model
{
    public const SCALES = [
        'nrs' => 'Escala numérica (NRS)',
        'visual' => 'Escala visual análoga',
        'cpot' => 'CPOT',
        'bps' => 'BPS',
        'bps_ni' => 'BPS-NI',
        'other' => 'Otra aprobada',
    ];

    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
            'self_report_capable' => 'boolean',
            'reassessed_at' => 'datetime',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }
}
