<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'pics_case_id', 'type', 'title', 'description', 'scheduled_at', 'responsible_user_id',
    'status', 'completed_at', 'completed_by', 'notes', 'created_by',
    'patient_response', 'patient_response_at', 'patient_responded_by_type', 'patient_responded_by_id',
])]
class PicsAgendaItem extends Model
{
    public const TYPES = [
        'cita' => 'Cita',
        'terapia' => 'Terapia',
        'tarea' => 'Tarea',
        'recordatorio' => 'Recordatorio',
    ];

    public const STATUSES = [
        'pendiente' => 'Pendiente',
        'completada' => 'Completada',
        'cancelada' => 'Cancelada',
    ];

    /** Tipos que el paciente/cuidador puede confirmar o rechazar desde el calendario. */
    public const RESPONDABLE_TYPES = ['cita', 'terapia'];

    public const RESPONSES = [
        'confirmada' => 'Confirmada',
        'no_asistira' => 'No podrá asistir',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'patient_response_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function patientRespondedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isRespondable(): bool
    {
        return in_array($this->type, self::RESPONDABLE_TYPES, true);
    }

    public function patientResponseLabel(): ?string
    {
        return $this->patient_response ? (self::RESPONSES[$this->patient_response] ?? $this->patient_response) : null;
    }
}
