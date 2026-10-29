<?php

namespace App\Models;

use App\Enums\EvidenceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['clinical_program_id', 'replaces_evidence_document_id', 'title', 'institutional_code', 'description', 'evidence_type', 'version', 'approved_on', 'expires_on', 'next_review_on', 'socialization_status', 'owner_id', 'status', 'document_reference', 'observations', 'quality_validated', 'validated_by', 'validated_at', 'uploaded_by'])]
class EvidenceDocument extends Model
{
    public const SOCIALIZATION_STATUSES = [
        'pending' => 'Pendiente',
        'in_progress' => 'En socialización',
        'completed' => 'Socializado',
    ];

    protected function casts(): array
    {
        return [
            'approved_on' => 'date',
            'expires_on' => 'date',
            'next_review_on' => 'date',
            'quality_validated' => 'boolean',
            'validated_at' => 'datetime',
            'status' => EvidenceStatus::class,
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function elements(): BelongsToMany
    {
        return $this->belongsToMany(MeasurableElement::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_evidence_document_id');
    }

    public function replacedBy(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_evidence_document_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_on?->isBefore(today()) ?? false;
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(EvidenceAcknowledgement::class);
    }
}
