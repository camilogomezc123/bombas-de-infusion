<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'care_plan_id', 'version', 'general_objective', 'discharge_criteria', 'disciplines',
    'effective_from', 'effective_until', 'changed_by',
])]
class CarePlanVersion extends Model
{
    protected function casts(): array
    {
        return [
            'disciplines' => 'array',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
