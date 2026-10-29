<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['committee_meeting_id', 'decision', 'rationale'])]
class MeetingDecision extends Model
{
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(CommitteeMeeting::class, 'committee_meeting_id');
    }
}
