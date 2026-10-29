<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acs_case_id', 'scheduled_on', 'contacted_at', 'milestone', 'contact_achieved', 'bleeding', 'readmission', 'reinfarction', 'rehabilitation_started', 'mortality', 'symptoms', 'adherence_and_access', 'observations'])]
class AcsFollowup extends Model
{
    protected function casts(): array
    {
        return ['scheduled_on' => 'date', 'contacted_at' => 'datetime', 'contact_achieved' => 'boolean', 'bleeding' => 'boolean', 'readmission' => 'boolean', 'reinfarction' => 'boolean', 'rehabilitation_started' => 'boolean', 'mortality' => 'boolean'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AcsCase::class, 'acs_case_id');
    }
}
