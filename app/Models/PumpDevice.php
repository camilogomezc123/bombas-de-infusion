<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'site_id', 'icu_unit_id', 'hospital_device_id', 'serial_number', 'model',
    'device_group', 'current_bed_label', 'is_active',
])]
class PumpDevice extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function icuUnit(): BelongsTo
    {
        return $this->belongsTo(IcuUnit::class);
    }

    public function infusionEvents(): HasMany
    {
        return $this->hasMany(InfusionEvent::class);
    }
}
