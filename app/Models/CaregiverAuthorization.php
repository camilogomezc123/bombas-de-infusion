<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pics_case_id', 'caregiver_id', 'relationship', 'is_primary', 'is_emergency_contact',
    'participates_in_decisions', 'preferred_language', 'support_level', 'communication_notes',
    'can_write_diary', 'can_access_journey',
    'authorized_by', 'authorized_at', 'revoked_by', 'revoked_at', 'last_inactivity_nudge_at',
])]
class CaregiverAuthorization extends Model
{
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_emergency_contact' => 'boolean',
            'participates_in_decisions' => 'boolean',
            'can_write_diary' => 'boolean',
            'can_access_journey' => 'boolean',
            'authorized_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_inactivity_nudge_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
