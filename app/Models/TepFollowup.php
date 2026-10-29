<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tep_case_id', 'milestone', 'scheduled_on', 'contacted_at', 'contact_achieved', 'persistent_dyspnea', 'bleeding', 'recurrence', 'readmission', 'mortality', 'adherence_and_access', 'observations'])]
class TepFollowup extends Model
{
    protected function casts(): array
    {
        return ['scheduled_on' => 'date', 'contacted_at' => 'datetime', 'contact_achieved' => 'boolean', 'persistent_dyspnea' => 'boolean', 'bleeding' => 'boolean', 'recurrence' => 'boolean', 'readmission' => 'boolean', 'mortality' => 'boolean'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(TepCase::class, 'tep_case_id');
    }
}
