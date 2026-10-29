<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'pics_case_id', 'authorable_type', 'authorable_id', 'entry_date', 'content',
    'message_to_patient', 'meaningful_memory', 'is_draft', 'visible_to_patient',
])]
class DiaryEntry extends Model
{
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'is_draft' => 'boolean',
            'visible_to_patient' => 'boolean',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function authorable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Patient usa "full_name", Caregiver usa "name" — un solo lugar para no repetir
     * ese detalle en cada vista que muestra quién escribió la entrada.
     */
    public function authorLabel(): string
    {
        return match (true) {
            $this->authorable instanceof Patient => $this->authorable->full_name,
            $this->authorable instanceof Caregiver => $this->authorable->name,
            default => 'Desconocido',
        };
    }
}
