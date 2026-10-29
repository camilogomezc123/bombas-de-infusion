<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acs_case_id', 'auditor_id', 'priority_reason', 'status', 'criteria_met', 'criteria_not_met', 'barriers', 'probable_cause', 'conclusion', 'recommendation', 'requires_five_whys', 'requires_phva', 'completed_at'])]
class AcsCaseAudit extends Model
{
    protected function casts(): array
    {
        return ['criteria_met' => 'array', 'criteria_not_met' => 'array', 'requires_five_whys' => 'boolean', 'requires_phva' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AcsCase::class, 'acs_case_id');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }
}
