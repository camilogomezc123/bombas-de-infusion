<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tep_case_id', 'medication', 'ordered_at', 'started_at', 'phase', 'documented_dose', 'contraindication_or_omission_reason', 'transition_plan'])]
class TepAnticoagulationEpisode extends Model
{
    protected function casts(): array
    {
        return ['ordered_at' => 'datetime', 'started_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(TepCase::class, 'tep_case_id');
    }
}
