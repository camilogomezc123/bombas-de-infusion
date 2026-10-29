<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinical_program_id', 'sort_order', 'description', 'protocol_section', 'risk', 'priority',
    'responsible_user_id', 'decision', 'proposed_adjustment', 'status', 'evidence_reference', 'closed_on',
])]
class ProtocolGap extends Model
{
    protected function casts(): array
    {
        return ['closed_on' => 'date'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
