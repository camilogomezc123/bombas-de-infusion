<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinical_program_id', 'activity', 'responsible', 'approver', 'consulted', 'informed',
    'service', 'valid_from', 'valid_until',
])]
class RaciAssignment extends Model
{
    protected function casts(): array
    {
        return ['valid_from' => 'date', 'valid_until' => 'date'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }
}
