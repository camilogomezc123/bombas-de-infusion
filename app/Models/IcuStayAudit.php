<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'auditor_id', 'assessment_finding_id', 'status', 'criteria_met', 'criteria_not_met', 'barriers',
    'probable_cause', 'conclusion', 'recommendation', 'requires_phva', 'completed_at',
])]
class IcuStayAudit extends Model
{
    public const STATUSES = [
        'pending' => 'Pendiente',
        'in_progress' => 'En análisis',
        'completed' => 'Completada',
    ];

    protected function casts(): array
    {
        return [
            'criteria_met' => 'array',
            'criteria_not_met' => 'array',
            'requires_phva' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(AssessmentFinding::class, 'assessment_finding_id');
    }
}
