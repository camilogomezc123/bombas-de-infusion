<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acs_case_id', 'started_at', 'first_device_at', 'completed_at', 'operator', 'vascular_access', 'culprit_artery', 'initial_flow', 'final_flow', 'multivessel_disease', 'intracoronary_imaging', 'intervention_type', 'successful', 'complications', 'complete_revascularization_plan'])]
class AcsPciProcedure extends Model
{
    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'first_device_at' => 'datetime', 'completed_at' => 'datetime', 'multivessel_disease' => 'boolean', 'intracoronary_imaging' => 'boolean', 'successful' => 'boolean'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AcsCase::class, 'acs_case_id');
    }
}
