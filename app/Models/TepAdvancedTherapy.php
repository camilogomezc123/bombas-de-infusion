<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tep_case_id', 'therapy_type', 'decided_at', 'started_at', 'completed', 'indication', 'contraindications', 'outcome'])]
class TepAdvancedTherapy extends Model
{
    protected function casts(): array
    {
        return ['decided_at' => 'datetime', 'started_at' => 'datetime', 'completed' => 'boolean'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(TepCase::class, 'tep_case_id');
    }
}
