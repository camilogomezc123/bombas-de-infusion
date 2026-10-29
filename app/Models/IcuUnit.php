<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['clinical_program_id', 'site_id', 'name', 'code', 'unit_type', 'bed_count', 'is_active'])]
class IcuUnit extends Model
{
    public const UNIT_TYPES = [
        'adult' => 'UCI de adultos',
        'intermediate' => 'Cuidado intermedio',
        'cardiovascular' => 'UCI cardiovascular',
        'surgical' => 'UCI quirúrgica',
        'neuro' => 'UCI neurológica',
        'oncologic' => 'UCI oncológica',
        'other' => 'Otra',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function stays(): HasMany
    {
        return $this->hasMany(IcuStay::class);
    }
}
