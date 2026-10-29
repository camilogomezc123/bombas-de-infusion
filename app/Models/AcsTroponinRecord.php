<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acs_case_id', 'collected_at', 'resulted_at', 'value', 'unit', 'high_sensitivity', 'interpretation'])]
class AcsTroponinRecord extends Model
{
    protected function casts(): array
    {
        return ['collected_at' => 'datetime', 'resulted_at' => 'datetime', 'high_sensitivity' => 'boolean', 'value' => 'decimal:4'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AcsCase::class, 'acs_case_id');
    }
}
