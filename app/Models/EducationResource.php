<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo reutilizable de contenido educativo: el staff lo redacta una sola vez y
 * luego lo asigna (EducationAssignment) a los casos donde aplica, en vez de reescribir
 * el mismo contenido por paciente. No confundir con ProgramResource, que es
 * infraestructura física del programa, no contenido educativo.
 */
#[Fillable(['title', 'category', 'audience', 'body', 'is_active', 'created_by', 'updated_by'])]
class EducationResource extends Model
{
    public const CATEGORIES = [
        'respiratorio' => 'Recuperación respiratoria',
        'movilidad' => 'Movilidad y fuerza',
        'cognitivo' => 'Memoria y cognición',
        'emocional' => 'Salud emocional',
        'nutricion' => 'Nutrición',
        'cuidador' => 'Cuidado del cuidador',
        'general' => 'General',
        'otro' => 'Otro',
    ];

    public const AUDIENCES = [
        'paciente' => 'Paciente',
        'cuidador' => 'Cuidador',
        'ambos' => 'Ambos',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EducationAssignment::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? (string) $this->category;
    }
}
