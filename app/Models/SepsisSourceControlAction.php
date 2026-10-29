<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sepsis_case_id', 'specialty', 'requested_at', 'assessed_at', 'decision',
    'procedure', 'performed_at', 'barriers', 'result',
])]
class SepsisSourceControlAction extends Model
{
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'assessed_at' => 'datetime',
            'performed_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SepsisCase::class, 'sepsis_case_id');
    }
}
