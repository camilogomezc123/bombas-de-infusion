<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Recordatorio personal que el paciente o el cuidador agrega a su propio calendario
 * (ej. "tomar agua", "llamar a mi hermana") — no es una cita clínica ni algo que el
 * staff revise. Privado por actor: cada quien solo ve los suyos, nunca los de otro
 * actor del mismo caso, y no se expone en ningún recurso de /pics.
 */
#[Fillable(['pics_case_id', 'title', 'remind_at', 'notes', 'created_by_type', 'created_by_id'])]
class PersonalReminder extends Model
{
    protected function casts(): array
    {
        return ['remind_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }
}
