<?php

namespace App\Models;

use App\Enums\ProgramRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'clinical_program_id', 'user_id', 'role', 'permissions', 'starts_on',
    'ends_on', 'is_active', 'granted_by',
    'discipline', 'service', 'route_role', 'required_competencies', 'evaluated_competencies',
    'competency_evaluated_on', 'competency_result', 'competency_valid_until', 'retraining_required',
])]
class ProgramMember extends Model
{
    protected function casts(): array
    {
        return [
            'role' => ProgramRole::class,
            'permissions' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
            'competency_evaluated_on' => 'date',
            'competency_valid_until' => 'date',
            'retraining_required' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staffCompetencies(): HasMany
    {
        return $this->hasMany(StaffCompetency::class);
    }
}
