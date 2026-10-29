<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'clinical_program_id', 'user_id', 'auditable_type', 'auditable_id', 'event',
    'field', 'old_value', 'new_value', 'change_reason', 'approved_by',
    'ip_address', 'user_agent', 'metadata',
])]
class ClinicalAudit extends Model
{
    public const SENSITIVE_FIELDS = [
        'password', 'remember_token', 'token', 'session', 'secret',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
