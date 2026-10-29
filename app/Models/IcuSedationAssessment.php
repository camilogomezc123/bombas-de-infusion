<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'assessed_at', 'goal_scale', 'goal_value', 'actual_value', 'indication', 'analgesics',
    'sedatives', 'infusions', 'boluses', 'nmb_used', 'deep_sedation_justification', 'withdrawal_risk',
    'withdrawal_assessment', 'adverse_event',
])]
class IcuSedationAssessment extends Model
{
    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
            'nmb_used' => 'boolean',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }

    public function matchesGoal(): ?bool
    {
        if (blank($this->goal_value) || blank($this->actual_value)) {
            return null;
        }

        return $this->goal_value === $this->actual_value;
    }
}
