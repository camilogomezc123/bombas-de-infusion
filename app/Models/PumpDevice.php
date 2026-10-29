<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'site_id', 'icu_unit_id', 'hospital_device_id', 'serial_number', 'model',
    'device_group', 'current_bed_label', 'is_active',
    'status', 'battery_pct', 'channel_count', 'last_status_at',
])]
class PumpDevice extends Model
{
    public const STATUSES = [
        'infusing' => 'Infundiendo',
        'paused' => 'En pausa',
        'occlusion' => 'Oclusión',
        'air_in_line' => 'Aire en línea',
        'battery_low' => 'Batería baja',
        'completed' => 'Completada',
        'idle' => 'Inactiva',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'battery_pct' => 'integer',
            'channel_count' => 'integer',
            'last_status_at' => 'datetime',
        ];
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            'infusing' => 'success',
            'battery_low' => 'warning',
            'occlusion', 'air_in_line' => 'danger',
            'completed' => 'info',
            default => 'gray',
        };
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
