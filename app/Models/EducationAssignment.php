<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * La personalización: qué contenido del catálogo (EducationResource) aplica a este
 * caso, y por qué (notes). El paciente/cuidador lo marca como leído desde el portal —
 * sin verificación profesional de comprensión (a diferencia de DischargeReadinessItem),
 * porque es consulta informativa continua, no un requisito de egreso.
 */
#[Fillable([
    'pics_case_id', 'education_resource_id', 'assigned_by', 'assigned_at', 'notes',
    'viewed_by_type', 'viewed_by_id', 'viewed_at',
    'response', 'feedback', 'completed_by_type', 'completed_by_id', 'completed_at',
    'validated_by', 'validated_at', 'validation_notes',
])]
class EducationAssignment extends Model
{
    public const RESPONSES = [
        'comprendido' => 'Lo comprendí',
        'necesito_apoyo' => 'Necesito aclaración o apoyo',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'viewed_at' => 'datetime',
            'completed_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(EducationResource::class, 'education_resource_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function viewedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function completedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isViewed(): bool
    {
        return $this->viewed_at !== null;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }

    public function responseLabel(): ?string
    {
        return $this->response ? (self::RESPONSES[$this->response] ?? $this->response) : null;
    }
}
