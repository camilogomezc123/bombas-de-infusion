<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acs_case_id', 'type', 'recognized_at', 'severity', 'management', 'outcome'])]
class AcsComplication extends Model
{
    protected function casts(): array
    {
        return ['recognized_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AcsCase::class, 'acs_case_id');
    }
}
