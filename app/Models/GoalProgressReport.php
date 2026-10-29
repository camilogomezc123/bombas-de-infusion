<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'recovery_goal_id', 'reporter_type', 'reporter_id', 'reported_at', 'notes', 'had_difficulty',
    'difficulty_reason', 'difficulty_reason_other',
    'validated_by', 'validated_at', 'validation_notes',
])]
class GoalProgressReport extends Model
{
    public const DIFFICULTY_REASONS = [
        'cansancio' => 'Cansancio',
        'dolor' => 'Dolor',
        'falta_ayuda' => 'Falta de ayuda',
        'dificultad_comprension' => 'Dificultad para comprender la actividad',
        'otra' => 'Otra razón',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'had_difficulty' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(RecoveryGoal::class, 'recovery_goal_id');
    }

    public function reporter(): MorphTo
    {
        return $this->morphTo();
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }
}
