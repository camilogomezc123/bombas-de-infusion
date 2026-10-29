<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'indicator_definition_id', 'version', 'numerator', 'denominator', 'exclusion_criteria',
    'formula', 'validation_method', 'target_value', 'effective_from', 'effective_until', 'changed_by',
])]
class IndicatorDefinitionVersion extends Model
{
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(IndicatorDefinition::class, 'indicator_definition_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
