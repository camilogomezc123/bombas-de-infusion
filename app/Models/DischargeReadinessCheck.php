<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'pics_case_id', 'responsible_user_id', 'completed_at', 'notes', 'created_by', 'updated_by',
])]
class DischargeReadinessCheck extends Model
{
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DischargeReadinessItem::class);
    }

    /**
     * Porcentaje de temas obligatorios cuya comprensión ya fue verificada por el
     * profesional (understood === true). No inventa un puntaje clínico: solo cuenta
     * lo que hay.
     *
     * @return array{percentage: float, pending: \Illuminate\Support\Collection}
     */
    public function readinessSummary(): array
    {
        $items = $this->items;
        $understood = $items->where('understood', true);
        $pending = $items->filter(fn (DischargeReadinessItem $item): bool => $item->understood !== true);

        $percentage = $items->isEmpty() ? 0.0 : round(($understood->count() / $items->count()) * 100, 1);

        return ['percentage' => $percentage, 'pending' => $pending->values()];
    }
}
