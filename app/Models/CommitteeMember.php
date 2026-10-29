<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['program_committee_id', 'user_id', 'display_name', 'email', 'discipline', 'committee_role', 'is_active'])]
class CommitteeMember extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(ProgramCommittee::class, 'program_committee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notificationEmail(): ?string
    {
        return $this->email ?: $this->user?->email;
    }
}
