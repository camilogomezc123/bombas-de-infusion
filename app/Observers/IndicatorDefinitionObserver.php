<?php

namespace App\Observers;

use App\Models\IndicatorDefinition;

/**
 * Gobierno de la ficha técnica: cuando cambia numerador/denominador/exclusiones/fórmula/
 * método de validación/meta, la versión anterior se archiva en indicator_definition_versions
 * con su vigencia cerrada, y la definición avanza de versión. Así un resultado histórico
 * nunca queda atribuido silenciosamente a una fórmula distinta de la que estaba vigente
 * cuando se registró.
 */
class IndicatorDefinitionObserver
{
    private const VERSIONED_FIELDS = [
        'numerator', 'denominator', 'exclusion_criteria', 'formula', 'validation_method', 'target_value',
    ];

    public function creating(IndicatorDefinition $definition): void
    {
        $definition->version ??= 1;
        $definition->effective_from ??= today();
    }

    public function updating(IndicatorDefinition $definition): void
    {
        $changedVersionedFields = collect(self::VERSIONED_FIELDS)
            ->filter(fn (string $field): bool => $definition->isDirty($field));

        if ($changedVersionedFields->isEmpty()) {
            return;
        }

        $definition->versions()->create([
            'version' => $definition->version ?: 1,
            'numerator' => $definition->getOriginal('numerator'),
            'denominator' => $definition->getOriginal('denominator'),
            'exclusion_criteria' => $definition->getOriginal('exclusion_criteria'),
            'formula' => $definition->getOriginal('formula'),
            'validation_method' => $definition->getOriginal('validation_method'),
            'target_value' => $definition->getOriginal('target_value'),
            'effective_from' => $definition->getOriginal('effective_from') ?? $definition->getOriginal('created_at'),
            'effective_until' => today(),
            'changed_by' => auth()->id(),
        ]);

        $definition->version = ($definition->version ?: 1) + 1;
        $definition->effective_from = today();
    }
}
