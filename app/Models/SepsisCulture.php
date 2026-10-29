<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sepsis_case_id', 'site', 'taken_at', 'microorganism', 'susceptibility', 'contamination'])]
class SepsisCulture extends Model
{
    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
            'contamination' => 'boolean',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SepsisCase::class, 'sepsis_case_id');
    }
}
