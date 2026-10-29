<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan interdisciplinario formal del caso: objetivo general, criterios de egreso y el
 * aporte de cada disciplina (medicina, enfermería, terapias, psicología, trabajo social,
 * nutrición). "disciplines" se guarda como JSON en vez de una tabla relacional
 * justamente para poder duplicarlo completo en cada versión (ver CarePlanObserver).
 */
#[Fillable([
    'pics_case_id', 'version', 'effective_from', 'general_objective', 'discharge_criteria',
    'disciplines', 'created_by', 'updated_by',
])]
class CarePlan extends Model
{
    public const DISCIPLINES = [
        'medicina' => 'Medicina',
        'enfermeria' => 'Enfermería',
        'terapia_fisica' => 'Terapia física',
        'terapia_ocupacional' => 'Terapia ocupacional',
        'fonoaudiologia' => 'Fonoaudiología',
        'psicologia' => 'Psicología',
        'trabajo_social' => 'Trabajo social',
        'nutricion' => 'Nutrición',
        'otro' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'disciplines' => 'array',
            'effective_from' => 'date',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    /**
     * Historial de versiones: se archiva un snapshot completo cada vez que cambia el
     * objetivo, los criterios de egreso o el plan por disciplina (CarePlanObserver).
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CarePlanVersion::class)->orderByDesc('version');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
