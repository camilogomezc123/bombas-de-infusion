<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'clinical_program_id', 'name', 'role_key', 'description', 'training_method',
    'evaluation_method', 'minimum_score', 'periodicity',
])]
class Competency extends Model
{
    protected function casts(): array
    {
        return ['minimum_score' => 'decimal:2'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function staffCompetencies(): HasMany
    {
        return $this->hasMany(StaffCompetency::class);
    }
}
