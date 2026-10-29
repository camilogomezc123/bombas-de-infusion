<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'assessment_finding_id', 'action', 'phase', 'indicator_definition_id', 'target', 'frequency',
    'evidence_expected', 'evidence_file_path', 'progress_percentage',
    'responsible_user_id', 'due_on', 'status', 'completed_at', 'effectiveness_verification',
])]
class CorrectiveAction extends Model
{
    public const PHASES = [
        'plan' => 'Planear',
        'do' => 'Hacer',
        'check' => 'Verificar',
        'act' => 'Actuar',
    ];

    public const STATUSES = [
        'open' => 'Abierta',
        'in_execution' => 'En ejecución',
        'pending_verification' => 'Pendiente de verificación',
        'effective' => 'Efectiva',
        'closed' => 'Cerrada',
        'not_effective' => 'No efectiva',
        'reopened' => 'Reabierta',
    ];

    public const CLOSED_STATUSES = ['effective', 'closed'];

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'completed_at' => 'datetime',
            'progress_percentage' => 'integer',
        ];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(AssessmentFinding::class, 'assessment_finding_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function indicatorDefinition(): BelongsTo
    {
        return $this->belongsTo(IndicatorDefinition::class);
    }

    /**
     * "Empieza con verbo en infinitivo" — heurística: la primera palabra termina en ar/er/ir.
     */
    public static function startsWithInfinitiveVerb(?string $action): bool
    {
        if (! $action) {
            return false;
        }

        $firstWord = strtok(trim($action), " \t\n");

        return (bool) preg_match('/^[A-ZÁÉÍÓÚÑ][a-záéíóúñ]*(ar|er|ir)$/u', (string) $firstWord);
    }
}
