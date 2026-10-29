<?php

namespace App\Observers;

use App\Models\CarePlan;

/**
 * Gobierno del plan interdisciplinario: cuando cambia el objetivo general, los criterios
 * de egreso o el plan por disciplina, la versión anterior se archiva completa en
 * care_plan_versions con su vigencia cerrada, y el plan avanza de versión. Mismo patrón
 * que IndicatorDefinitionObserver — un plan histórico nunca queda reescrito en silencio.
 */
class CarePlanObserver
{
    private const VERSIONED_FIELDS = ['general_objective', 'discharge_criteria', 'disciplines'];

    public function creating(CarePlan $plan): void
    {
        $plan->version ??= 1;
        $plan->effective_from ??= today();
        $plan->created_by ??= auth('web')->id();
        $plan->updated_by ??= auth('web')->id();
    }

    public function updating(CarePlan $plan): void
    {
        $plan->updated_by = auth('web')->id();

        $changed = collect(self::VERSIONED_FIELDS)->filter(fn (string $field): bool => $plan->isDirty($field));

        if ($changed->isEmpty()) {
            return;
        }

        $plan->versions()->create([
            'version' => $plan->version ?: 1,
            'general_objective' => $plan->getOriginal('general_objective'),
            'discharge_criteria' => $plan->getOriginal('discharge_criteria'),
            'disciplines' => $plan->getOriginal('disciplines'),
            'effective_from' => $plan->getOriginal('effective_from') ?? $plan->getOriginal('created_at'),
            'effective_until' => today(),
            'changed_by' => auth('web')->id(),
        ]);

        $plan->version = ($plan->version ?: 1) + 1;
        $plan->effective_from = today();
    }
}
