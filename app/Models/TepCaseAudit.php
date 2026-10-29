<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tep_case_id', 'auditor_id', 'status', 'criteria_met', 'criteria_not_met', 'barriers', 'probable_cause', 'conclusion', 'recommendation', 'requires_phva', 'completed_at'])]
class TepCaseAudit extends Model
{
    protected function casts(): array
    {
        return ['criteria_met' => 'array', 'criteria_not_met' => 'array', 'requires_phva' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(TepCase::class, 'tep_case_id');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }
}
