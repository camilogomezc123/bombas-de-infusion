<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pics_case_id', 'pics_followup_id', 'specialty', 'status', 'referred_at', 'scheduled_at', 'completed_at', 'notes',
])]
class PicsReferral extends Model
{
    public const STATUSES = [
        'open' => 'Pendiente de agendar',
        'scheduled' => 'Agendada',
        'completed' => 'Completada',
        'no_show' => 'Inasistencia',
    ];

    protected function casts(): array
    {
        return [
            'referred_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function followup(): BelongsTo
    {
        return $this->belongsTo(PicsFollowup::class, 'pics_followup_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
