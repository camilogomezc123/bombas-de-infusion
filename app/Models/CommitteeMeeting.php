<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_committee_id', 'title', 'scheduled_at', 'ended_at', 'status', 'agenda', 'minutes', 'minutes_file_path'])]
class CommitteeMeeting extends Model
{
    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(ProgramCommittee::class, 'program_committee_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(MeetingDecision::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(MeetingAction::class);
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(CommitteeMember::class, 'meeting_attendees')
            ->withPivot('attended')
            ->withTimestamps();
    }
}
