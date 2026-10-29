<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pump_device_id', 'icu_stay_id', 'drug_infusion_profile_id', 'vendor_infusion_id', 'occurred_at',
    'medication_name_raw', 'concentration_raw', 'limit_type', 'alert_level', 'next_action', 'care_area',
    'attempted_value', 'attempted_unit', 'soft_limit_low', 'soft_limit_high', 'hard_limit_low',
    'hard_limit_high', 'existing_dose_rate_value', 'existing_dose_rate_unit', 'next_drug',
    'raw_payload', 'ingested_at',
    'channel_number', 'device_status', 'battery_pct', 'volume_remaining_ml',
    'infusion_duration_planned_minutes', 'used_drug_library', 'overridden_by_operator',
    'co_signed_by', 'co_signed_at',
])]
class InfusionEvent extends Model
{
    /**
     * Categorías de fármaco donde se espera doble verificación (co-firma) de la
     * programación normal, no solo de las anulaciones de límite — alto riesgo si se
     * programa mal: vasoactivos, opioides, sedación.
     */
    public const HIGH_RISK_CATEGORIES = ['vasoactive', 'analgesia', 'sedation'];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'attempted_value' => 'decimal:4',
            'soft_limit_low' => 'decimal:4',
            'soft_limit_high' => 'decimal:4',
            'hard_limit_low' => 'decimal:4',
            'hard_limit_high' => 'decimal:4',
            'existing_dose_rate_value' => 'decimal:4',
            'raw_payload' => 'array',
            'ingested_at' => 'datetime',
            'channel_number' => 'integer',
            'battery_pct' => 'integer',
            'volume_remaining_ml' => 'decimal:2',
            'infusion_duration_planned_minutes' => 'integer',
            'used_drug_library' => 'boolean',
            'co_signed_at' => 'datetime',
        ];
    }

    public function pumpDevice(): BelongsTo
    {
        return $this->belongsTo(PumpDevice::class);
    }

    public function coSignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'co_signed_by');
    }

    public function requiresCoSign(): bool
    {
        return in_array($this->drugInfusionProfile?->category, self::HIGH_RISK_CATEGORIES, true);
    }

    public function icuStay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class);
    }

    public function drugInfusionProfile(): BelongsTo
    {
        return $this->belongsTo(DrugInfusionProfile::class);
    }

    public function isHardLimitOverride(): bool
    {
        return str_contains((string) $this->alert_level, 'Duro')
            && in_array($this->next_action, ['Anulaciones', 'Retrocesos'], true);
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('icu_stay_id');
    }
}
