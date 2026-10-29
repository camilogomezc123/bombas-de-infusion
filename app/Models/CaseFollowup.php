<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'acv_case_id', 'user_id', 'contacted_at', 'is_effective',
    'rankin_90_days', 'observations', 'is_auto_closed',
])]
class CaseFollowup extends Model
{
    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'is_effective' => 'boolean',
            'is_auto_closed' => 'boolean',
            'rankin_90_days' => 'integer',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AcvCase::class, 'acv_case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        if ($this->is_auto_closed) {
            return 'Cerrado automáticamente';
        }

        return $this->is_effective ? 'Efectivo' : 'No efectivo';
    }
}
