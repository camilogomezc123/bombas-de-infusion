<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'performed_at', 'safety_screen_passed', 'tool', 'goal_level', 'baseline_level',
    'achieved_level', 'duration_minutes', 'distance_meters', 'participants', 'barrier', 'adverse_event',
    'next_plan',
])]
class IcuMobilitySession extends Model
{
    public const LEVELS = [
        '0' => 'Reposicionamiento',
        '1' => 'Rango pasivo',
        '2' => 'Ejercicio activo en cama',
        '3' => 'Sedestación en cama',
        '4' => 'Sedestación al borde',
        '5' => 'Transferencia a silla',
        '6' => 'Bipedestación',
        '7' => 'Marcha en sitio',
        '8' => 'Deambulación',
        '9' => 'Otro nivel configurable',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'safety_screen_passed' => 'boolean',
            'distance_meters' => 'decimal:1',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }
}
