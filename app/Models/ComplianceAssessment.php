<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['measurable_element_id', 'status', 'progress_percentage', 'observations', 'assessed_by', 'assessed_on', 'next_assessment_on'])]
class ComplianceAssessment extends Model
{
    protected function casts(): array
    {
        return ['assessed_on' => 'date', 'next_assessment_on' => 'date', 'progress_percentage' => 'integer'];
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(MeasurableElement::class, 'measurable_element_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AssessmentFinding::class);
    }
}
