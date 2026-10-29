<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['icu_stay_id', 'computed_at', 'vis_score', 'breakdown', 'weight_kg_used'])]
class VisScoreSnapshot extends Model
{
    protected function casts(): array
    {
        return [
            'computed_at' => 'datetime',
            'vis_score' => 'decimal:2',
            'breakdown' => 'array',
            'weight_kg_used' => 'decimal:2',
        ];
    }

    public function icuStay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class);
    }
}
