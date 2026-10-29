<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Bandeja básica de tareas: el paciente o el cuidador reportan una dificultad, el
 * profesional asignado responde, y el paciente ve esa respuesta — el ciclo que exige
 * la Etapa 1. `case()` (no `pics_case_id` directo en el nombre del método) permite
 * reutilizar el observer de auditoría genérico ya usado por PicsFollowup/PicsReferral.
 */
#[Fillable([
    'pics_case_id', 'type', 'description', 'priority', 'status',
    'created_by_type', 'created_by_id', 'assigned_to', 'review_deadline',
    'acknowledged_at', 'response_text', 'responded_by', 'responded_at', 'resolved_at',
])]
class SupportRequest extends Model
{
    public const TYPES = [
        'dificultad' => 'Dificultad',
        'duda_medicamento' => 'Duda sobre un medicamento',
    ];

    public const PRIORITIES = [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
    ];

    public const STATUSES = [
        'nueva' => 'Nueva',
        'asignada' => 'Asignada',
        'reconocida' => 'Recibida',
        'respondida' => 'Respondida',
        'escalada' => 'Escalada',
        'resuelta' => 'Resuelta',
    ];

    protected function casts(): array
    {
        return [
            'review_deadline' => 'date',
            'acknowledged_at' => 'datetime',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isAnswered(): bool
    {
        return $this->response_text !== null;
    }
}
