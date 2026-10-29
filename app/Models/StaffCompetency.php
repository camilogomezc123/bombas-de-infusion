<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'competency_id', 'program_member_id', 'approved_on', 'expires_on', 'score', 'result', 'evidence_reference',
])]
class StaffCompetency extends Model
{
    protected function casts(): array
    {
        return [
            'approved_on' => 'date',
            'expires_on' => 'date',
            'score' => 'decimal:2',
        ];
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(ProgramMember::class, 'program_member_id');
    }
}
