<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Cómo era el paciente antes de la UCI, su situación actual y las barreras para
 * recuperarse en casa. No calcula ningún porcentaje de recuperación — la comparación
 * entre el estado previo y el actual la hace la persona que lo lee, no el sistema.
 */
#[Fillable([
    'pics_case_id', 'mobility_before', 'autonomy_before', 'habitual_activities', 'supports_before',
    'current_situation', 'home_barriers', 'transport_barriers', 'companion_barriers', 'access_barriers',
    'responsible_user_id', 'reported_by_type', 'reported_by_id', 'reported_at',
    'is_confirmed', 'confirmed_by', 'confirmed_at',
])]
class RecoveryPassport extends Model
{
    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'is_confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function reportedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecoveryPassportItem::class);
    }

    public function needs(): HasMany
    {
        return $this->items()->where('type', RecoveryPassportItem::TYPE_NEED);
    }

    public function aspirations(): HasMany
    {
        return $this->items()->where('type', RecoveryPassportItem::TYPE_ASPIRATION);
    }
}
