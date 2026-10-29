<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Paso de la ruta propia del cuidador: orientación, autocuidado, red de apoyo,
 * preparación para el manejo en casa, seguimiento. El cuidador lo marca completado
 * desde el portal; el profesional lo confirma desde el caso — mismo patrón
 * reportado/confirmado que RecoveryPassport y SupportRequest.
 */
#[Fillable([
    'pics_case_id', 'title', 'description', 'category', 'sort_order', 'is_required',
    'reported_by_type', 'reported_by_id', 'reported_at', 'caregiver_notes',
    'confirmed_by', 'confirmed_at', 'created_by',
])]
class CaregiverJourneyStep extends Model
{
    public const CATEGORIES = [
        'orientacion' => 'Orientación inicial',
        'autocuidado' => 'Autocuidado del cuidador',
        'red_apoyo' => 'Red de apoyo',
        'preparacion_manejo' => 'Preparación para el manejo en casa',
        'seguimiento' => 'Seguimiento y contacto con el equipo',
        'otro' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'reported_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function reportedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCompleted(): bool
    {
        return $this->reported_at !== null;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
