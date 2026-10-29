<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['icu_stay_id', 'weight_kg', 'recorded_at', 'source', 'created_by'])]
class IcuStayWeight extends Model
{
    public const SOURCES = [
        'manual' => 'Registro manual',
        'admission' => 'Peso de ingreso',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function icuStay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class);
    }
}
