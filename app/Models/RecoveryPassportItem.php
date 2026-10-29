<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Una fila unifica "necesidad identificada" y "objetivo significativo en palabras del
 * paciente" (ej. "vestirme", "volver a cocinar") — son casi el mismo dato con un
 * matiz distinto, y separarlos en dos tablas solo duplicaría código sin aportar nada.
 */
#[Fillable(['recovery_passport_id', 'type', 'description', 'status', 'created_by_type', 'created_by_id'])]
class RecoveryPassportItem extends Model
{
    public const TYPE_NEED = 'necesidad';

    public const TYPE_ASPIRATION = 'objetivo_significativo';

    public const TYPES = [
        self::TYPE_NEED => 'Necesidad identificada',
        self::TYPE_ASPIRATION => 'Objetivo significativo para la persona',
    ];

    public const STATUSES = [
        'identificado' => 'Identificado',
        'en_progreso' => 'En progreso',
        'resuelto' => 'Resuelto',
    ];

    public function passport(): BelongsTo
    {
        return $this->belongsTo(RecoveryPassport::class, 'recovery_passport_id');
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }
}
