<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['committee_meeting_id', 'meeting_decision_id', 'action', 'responsible_user_id', 'responsible_name', 'due_on', 'status', 'closure_evidence_reference', 'completed_at'])]
class MeetingAction extends Model
{
    protected $attributes = ['status' => 'open'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'completed_at' => 'datetime'];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(CommitteeMeeting::class, 'committee_meeting_id');
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(MeetingDecision::class, 'meeting_decision_id');
    }
}
