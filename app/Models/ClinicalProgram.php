<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'name', 'short_name', 'description', 'purpose', 'mission', 'vision',
    'target_population', 'scope', 'inclusion_criteria', 'exclusion_criteria',
    'executive_sponsor', 'medical_leader', 'nursing_leader', 'quality_leader',
    'started_at', 'status', 'version', 'approved_at', 'next_review_at',
    'is_active', 'created_by', 'updated_by',
])]
class ClinicalProgram extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'approved_at' => 'datetime',
            'next_review_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProgramMember::class);
    }

    public function committees(): HasMany
    {
        return $this->hasMany(ProgramCommittee::class);
    }

    public function frameworks(): HasMany
    {
        return $this->hasMany(AccreditationFramework::class);
    }
}
