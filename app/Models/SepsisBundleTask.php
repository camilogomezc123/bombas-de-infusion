<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sepsis_case_id', 'task_key', 'label', 'status', 'target_at', 'done_at',
    'responsible_user_id', 'source', 'result', 'justification', 'escalated',
])]
class SepsisBundleTask extends Model
{
    public const STATUSES = [
        'pending' => 'Pendiente',
        'done' => 'Cumplido',
        'done_late' => 'Cumplido fuera de tiempo',
        'not_indicated' => 'No indicado',
        'contraindicated' => 'Contraindicado',
        'omitted' => 'Omitido',
        'unavailable' => 'No disponible',
        'requires_audit' => 'Requiere auditoría',
    ];

    protected function casts(): array
    {
        return [
            'target_at' => 'datetime',
            'done_at' => 'datetime',
            'escalated' => 'boolean',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SepsisCase::class, 'sepsis_case_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
