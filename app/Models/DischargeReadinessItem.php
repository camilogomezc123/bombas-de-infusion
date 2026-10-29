<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un tema del recorrido de comprensión previo al alta. Dos capas independientes:
 * "ya lo revisé" (reviewed_by/_at, desde el portal) y la verificación real de
 * comprensión por teach-back del profesional (understood + verified_by/_at) — el
 * teach-back ocurre presencialmente sin importar si el portal se usó o no.
 */
#[Fillable([
    'discharge_readiness_check_id', 'topic', 'custom_topic', 'staff_instructions',
    'verification_method', 'reviewed_by_type', 'reviewed_by_id', 'reviewed_at',
    'understood', 'verified_by', 'verified_at', 'notes', 'sort_order',
])]
class DischargeReadinessItem extends Model
{
    public const TOPICS = [
        'medicamentos' => 'Medicamentos y dosis',
        'signos_alarma' => 'Signos de alarma para volver a urgencias',
        'citas_control' => 'Citas de control programadas',
        'cuidados_en_casa' => 'Cuidados en casa',
        'contacto_emergencia' => 'A quién contactar ante dudas',
        'equipos_dispositivos' => 'Uso de equipos o dispositivos',
        'dieta_actividad' => 'Dieta y actividad recomendadas',
        'otro' => 'Otro',
    ];

    public const VERIFICATION_METHODS = [
        'teach_back' => 'Teach-back (explica con sus palabras)',
        'demostracion' => 'Demostración práctica',
        'material_escrito' => 'Material escrito entregado',
        'otro' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'understood' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function check(): BelongsTo
    {
        return $this->belongsTo(DischargeReadinessCheck::class, 'discharge_readiness_check_id');
    }

    public function reviewedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function topicLabel(): string
    {
        return $this->topic === 'otro' && $this->custom_topic
            ? $this->custom_topic
            : (self::TOPICS[$this->topic] ?? $this->topic);
    }
}
