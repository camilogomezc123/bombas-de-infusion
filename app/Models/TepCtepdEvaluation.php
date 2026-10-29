<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tep_case_id', 'evaluated_on', 'persistent_symptoms', 'study', 'result', 'ctepd_suspected', 'cteph_suspected', 'referral_plan'])]
class TepCtepdEvaluation extends Model
{
    protected function casts(): array
    {
        return ['evaluated_on' => 'date', 'persistent_symptoms' => 'boolean', 'ctepd_suspected' => 'boolean', 'cteph_suspected' => 'boolean'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(TepCase::class, 'tep_case_id');
    }
}
