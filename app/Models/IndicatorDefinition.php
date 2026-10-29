<?php

namespace App\Models;

use App\Services\AcsIndicatorService;
use App\Services\InfusionIndicatorService;
use App\Services\PicsIndicatorService;
use App\Services\SepsisIndicatorService;
use App\Services\TepIndicatorService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'clinical_program_id', 'code', 'name', 'indicator_group', 'type', 'objective', 'operational_definition',
    'numerator', 'denominator', 'exclusion_criteria', 'formula', 'unit', 'target_value', 'expected_direction',
    'source', 'validation_method', 'responsible_user_id', 'periodicity', 'baseline_value', 'is_core_indicator',
    'core_indicator_key', 'current_result', 'trend', 'observations', 'result_updated_at', 'sort_order',
    'version', 'effective_from',
])]
class IndicatorDefinition extends Model
{
    public const GROUPS = [
        'structure' => 'Estructura',
        'process' => 'Proceso',
        'result' => 'Resultado',
        'experience' => 'Experiencia y sostenibilidad',
    ];

    protected function casts(): array
    {
        return [
            'is_core_indicator' => 'boolean',
            'result_updated_at' => 'datetime',
            'effective_from' => 'date',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }

    /**
     * Historial de versiones de la definición (numerador/denominador/fórmula/exclusiones/
     * método de validación). Un resultado histórico no cambia retroactivamente cuando se
     * edita la definición: la versión anterior queda archivada aquí con su vigencia.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(IndicatorDefinitionVersion::class)->orderByDesc('version');
    }

    /**
     * Para los 6 indicadores institucionales, el resultado se LEE en vivo desde
     * SepsisIndicatorService — nunca se recalcula ni se guarda duplicado aquí.
     */
    public function liveResult(): ?float
    {
        if (! $this->is_core_indicator || ! $this->core_indicator_key) {
            return $this->current_result !== null ? (float) $this->current_result : null;
        }

        if ($this->program?->code === 'INFARTO') {
            $summary = app(AcsIndicatorService::class)->dashboard(now()->format('Y'));

            return $summary[$this->core_indicator_key] ?? null;
        }

        if ($this->program?->code === 'TEP') {
            $summary = app(TepIndicatorService::class)->dashboard(now()->format('Y'));

            return $summary[$this->core_indicator_key] ?? null;
        }

        if ($this->program?->code === 'PICS') {
            $summary = app(PicsIndicatorService::class)->summary(now()->format('Y'));

            return $summary[$this->core_indicator_key] ?? null;
        }

        if ($this->program?->code === 'BOMBAS') {
            $summary = app(InfusionIndicatorService::class)->summary();

            return $summary[$this->core_indicator_key] ?? null;
        }

        $summary = app(SepsisIndicatorService::class)->summary(now()->format('Y'));

        return $summary[$this->core_indicator_key] ?? null;
    }
}
