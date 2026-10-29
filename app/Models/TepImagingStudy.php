<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tep_case_id', 'study_type', 'ordered_at', 'performed_at', 'result', 'findings'])]
class TepImagingStudy extends Model
{
    protected function casts(): array
    {
        return ['ordered_at' => 'datetime', 'performed_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(TepCase::class, 'tep_case_id');
    }
}
