<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['accreditation_standard_id', 'code', 'name', 'description', 'institutional_interpretation', 'responsible_user_id', 'compliance_status', 'progress_percentage', 'required_evidence', 'evaluated_on', 'next_evaluation_on', 'observations', 'sort_order'])]
class MeasurableElement extends Model
{
    protected function casts(): array
    {
        return ['evaluated_on' => 'date', 'next_evaluation_on' => 'date', 'progress_percentage' => 'integer'];
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(AccreditationStandard::class, 'accreditation_standard_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(ComplianceAssessment::class);
    }

    public function evidenceDocuments(): BelongsToMany
    {
        return $this->belongsToMany(EvidenceDocument::class);
    }
}
