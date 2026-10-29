<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'compliance_assessment_id', 'severity', 'description', 'status',
    'why_1', 'why_2', 'why_3', 'why_4', 'why_5', 'root_cause',
    'validation_explains_gap', 'validation_logical_continuity', 'validation_no_repeated_causes',
    'validation_not_symptom', 'validation_intervention_reduces_gap', 'validation_not_only_consequence',
])]
class AssessmentFinding extends Model
{
    protected function casts(): array
    {
        return [
            'validation_explains_gap' => 'boolean',
            'validation_logical_continuity' => 'boolean',
            'validation_no_repeated_causes' => 'boolean',
            'validation_not_symptom' => 'boolean',
            'validation_intervention_reduces_gap' => 'boolean',
            'validation_not_only_consequence' => 'boolean',
        ];
    }

    /**
     * Todas las validaciones obligatorias del análisis de causa raíz quedaron marcadas.
     */
    public function hasCompleteRootCauseValidation(): bool
    {
        return $this->validation_explains_gap
            && $this->validation_logical_continuity
            && $this->validation_no_repeated_causes
            && $this->validation_not_symptom
            && $this->validation_intervention_reduces_gap
            && $this->validation_not_only_consequence;
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ComplianceAssessment::class, 'compliance_assessment_id');
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }
}
