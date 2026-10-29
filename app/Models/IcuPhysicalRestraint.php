<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'restraint_type', 'indication', 'started_at', 'reassessed_at',
    'alternatives_tried', 'removed_at', 'related_event',
])]
class IcuPhysicalRestraint extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'reassessed_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }

    public function isActive(): bool
    {
        return $this->removed_at === null;
    }
}
