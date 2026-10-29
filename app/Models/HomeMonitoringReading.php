<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registro manual de lecturas de monitoreo en casa (saturación, frecuencia cardíaca,
 * presión arterial, etc.), digitadas por el paciente o el cuidador desde el portal —
 * no hay integración real con ningún dispositivo/proveedor todavía. Sin capa de
 * confirmación profesional (igual que el diario): el staff solo consulta.
 */
#[Fillable([
    'pics_case_id', 'reading_type', 'value', 'unit', 'measured_at', 'notes',
    'recorded_by_type', 'recorded_by_id',
])]
class HomeMonitoringReading extends Model
{
    public const READING_TYPES = [
        'spo2' => 'Saturación de oxígeno (SpO2)',
        'heart_rate' => 'Frecuencia cardíaca',
        'blood_pressure' => 'Presión arterial',
        'temperature' => 'Temperatura',
        'respiratory_rate' => 'Frecuencia respiratoria',
        'glucose' => 'Glucosa',
        'weight' => 'Peso',
        'otro' => 'Otro',
    ];

    protected function casts(): array
    {
        return ['measured_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function recordedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return self::READING_TYPES[$this->reading_type] ?? $this->reading_type;
    }
}
