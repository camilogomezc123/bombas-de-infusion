<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinical_program_id', 'code', 'name', 'description', 'version', 'configuration',
    'source_document', 'source_section', 'status', 'effective_from', 'effective_until',
    'approved_by', 'approved_at', 'created_by', 'updated_by',
])]
class ClinicalRule extends Model
{
    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
