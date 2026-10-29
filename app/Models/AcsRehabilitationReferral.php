<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acs_case_id', 'referred_on', 'contacted_on', 'started_on', 'modality', 'sessions_planned', 'sessions_completed', 'status', 'barriers'])]
class AcsRehabilitationReferral extends Model
{
    protected function casts(): array
    {
        return ['referred_on' => 'date', 'contacted_on' => 'date', 'started_on' => 'date'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AcsCase::class, 'acs_case_id');
    }
}
