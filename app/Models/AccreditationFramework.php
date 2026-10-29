<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['clinical_program_id', 'name', 'publisher', 'edition', 'effective_from', 'effective_until', 'reference_url', 'is_active'])]
class AccreditationFramework extends Model
{
    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_until' => 'date', 'is_active' => 'boolean'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(AccreditationChapter::class);
    }
}
