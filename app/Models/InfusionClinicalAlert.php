<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'infusion_event_id', 'type', 'severity', 'message', 'status',
    'acknowledged_by', 'acknowledged_at', 'resolved_at', 'created_by', 'updated_by',
])]
class InfusionClinicalAlert extends Model
{
    public const TYPES = [
        'hard_limit_override' => 'Límite duro anulado/retrocedido',
        'max_dose_exceeded' => 'Dosis máxima institucional excedida',
        'pis_risk' => 'Riesgo de síndrome de infusión por propofol',
        'vis_critical' => 'Score VIS crítico',
    ];

    public const SEVERITIES = [
        'info' => 'Informativa',
        'warning' => 'Alerta',
        'critical' => 'Crítica',
    ];

    public const STATUSES = [
        'open' => 'Abierta',
        'acknowledged' => 'Reconocida',
        'resolved' => 'Resuelta',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function icuStay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class);
    }

    public function infusionEvent(): BelongsTo
    {
        return $this->belongsTo(InfusionEvent::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
