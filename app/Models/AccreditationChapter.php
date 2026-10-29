<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['accreditation_framework_id', 'code', 'name', 'description', 'sort_order'])]
class AccreditationChapter extends Model
{
    public function framework(): BelongsTo
    {
        return $this->belongsTo(AccreditationFramework::class, 'accreditation_framework_id');
    }

    public function standards(): HasMany
    {
        return $this->hasMany(AccreditationStandard::class);
    }
}
