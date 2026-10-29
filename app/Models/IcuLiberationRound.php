<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'round_date', 'participants', 'rass_sas_goal', 'rass_sas_actual', 'cam_icdsc_result',
    'analgesics_sedatives', 'barriers_to_wakefulness', 'adjustment_plan', 'pain_summary', 'sat_plan', 'sbt_plan',
    'delirium_summary', 'mobility_goal_text', 'restraints_reviewed', 'sleep_plan', 'family_engagement_notes',
    'nutrition_reviewed', 'devices_reviewed', 'pics_risk_reviewed', 'transfer_plan', 'goals_of_day', 'created_by',
])]
class IcuLiberationRound extends Model
{
    protected function casts(): array
    {
        return [
            'round_date' => 'date',
            'participants' => 'array',
            'restraints_reviewed' => 'boolean',
            'nutrition_reviewed' => 'boolean',
            'devices_reviewed' => 'boolean',
            'pics_risk_reviewed' => 'boolean',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
