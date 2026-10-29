<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinical_program_id', 'site_id', 'resource_name', 'service', 'availability',
    'responsible_user_id', 'verified_on', 'finding', 'contingency',
])]
class ProgramResource extends Model
{
    protected function casts(): array
    {
        return [
            'verified_on' => 'date',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
