<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tep_case_id', 'requested_at', 'activated_at', 'decision_at', 'participants', 'decision', 'rationale'])]
class TepPertActivation extends Model
{
    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'activated_at' => 'datetime', 'decision_at' => 'datetime', 'participants' => 'array'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(TepCase::class, 'tep_case_id');
    }
}
