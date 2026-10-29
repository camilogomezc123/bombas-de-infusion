<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'medication_reconciliation_id', 'medication_name', 'dose', 'route', 'frequency',
    'schedule_times', 'status', 'reconciliation_notes', 'patient_instructions', 'sort_order',
])]
class MedicationReconciliationItem extends Model
{
    protected function casts(): array
    {
        return ['schedule_times' => 'array'];
    }

    public const STATUSES = [
        'continua' => 'Continúa igual',
        'nueva' => 'Nueva',
        'suspendida' => 'Suspendida',
        'ajustada' => 'Dosis ajustada',
    ];

    public const ROUTES = [
        'oral' => 'Oral',
        'subcutanea' => 'Subcutánea',
        'intramuscular' => 'Intramuscular',
        'topica' => 'Tópica',
        'inhalada' => 'Inhalada',
        'otra' => 'Otra',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(MedicationReconciliation::class, 'medication_reconciliation_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
