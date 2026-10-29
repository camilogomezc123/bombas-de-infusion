<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['accreditation_chapter_id', 'code', 'name', 'description', 'sort_order', 'is_active'])]
class AccreditationStandard extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(AccreditationChapter::class, 'accreditation_chapter_id');
    }

    public function elements(): HasMany
    {
        return $this->hasMany(MeasurableElement::class);
    }
}
