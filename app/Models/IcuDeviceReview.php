<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'device_type', 'placed_at', 'still_needed', 'reviewed_at', 'removed_at', 'unplanned_removal',
])]
class IcuDeviceReview extends Model
{
    public const DEVICE_TYPES = [
        'urinary_catheter' => 'Catéter urinario',
        'cvc' => 'Catéter venoso central',
        'arterial_line' => 'Línea arterial',
        'ngt' => 'Sonda nasogástrica',
        'drain' => 'Drenaje',
        'other' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'placed_at' => 'datetime',
            'still_needed' => 'boolean',
            'reviewed_at' => 'datetime',
            'removed_at' => 'datetime',
            'unplanned_removal' => 'boolean',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }
}
