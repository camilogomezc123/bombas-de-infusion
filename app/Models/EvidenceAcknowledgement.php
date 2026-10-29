<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['evidence_document_id', 'program_member_id', 'method', 'acknowledged_on', 'notes'])]
class EvidenceAcknowledgement extends Model
{
    protected $table = 'evidence_document_acknowledgements';

    public const METHODS = [
        'read' => 'Lectura confirmada',
        'training' => 'Capacitación',
    ];

    protected function casts(): array
    {
        return ['acknowledged_on' => 'date'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EvidenceDocument::class, 'evidence_document_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(ProgramMember::class, 'program_member_id');
    }
}
