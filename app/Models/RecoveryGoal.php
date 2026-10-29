<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'pics_case_id', 'domain', 'description', 'measure', 'unit', 'assistance_level', 'target_date',
    'restrictions', 'responsible_user_id', 'review_criteria', 'status', 'created_by',
])]
class RecoveryGoal extends Model
{
    public const STATUSES = [
        'active' => 'Activa',
        'paused' => 'Pausada',
        'closed' => 'Cerrada',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
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

    public function progressReports(): HasMany
    {
        return $this->hasMany(GoalProgressReport::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
