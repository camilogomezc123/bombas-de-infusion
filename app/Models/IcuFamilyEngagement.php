<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'occurred_at', 'contact_name', 'relationship', 'communication_preferences', 'language',
    'spiritual_support', 'participated_in_round', 'participated_in_mobility', 'participated_in_reorientation',
    'education_provided', 'teach_back_confirmed', 'meeting_held', 'meeting_participants', 'meeting_objectives',
    'information_provided', 'understanding_level', 'questions', 'values_preferences', 'decisions', 'commitments',
    'next_meeting_at',
])]
class IcuFamilyEngagement extends Model
{
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'participated_in_round' => 'boolean',
            'participated_in_mobility' => 'boolean',
            'participated_in_reorientation' => 'boolean',
            'education_provided' => 'boolean',
            'teach_back_confirmed' => 'boolean',
            'meeting_held' => 'boolean',
            'next_meeting_at' => 'datetime',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }
}
