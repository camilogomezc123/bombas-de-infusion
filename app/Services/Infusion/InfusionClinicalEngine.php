<?php

namespace App\Services\Infusion;

use App\Models\IcuStay;
use App\Models\InfusionEvent;

/**
 * Orquesta el motor clínico tras cada corrida de ingesta: alertas por evento
 * (MaxDoseGuard) y recálculos por estancia afectada (dosis acumulada, VIS, riesgo PIS).
 */
class InfusionClinicalEngine
{
    public function __construct(
        private readonly MaxDoseGuard $maxDoseGuard,
        private readonly CumulativeDoseCalculator $cumulativeDoseCalculator,
        private readonly VisScoreCalculator $visScoreCalculator,
        private readonly PisRiskEvaluator $pisRiskEvaluator,
    ) {}

    public function process(IngestionResult $result): void
    {
        /** @var InfusionEvent $event */
        foreach ($result->events as $event) {
            $this->maxDoseGuard->evaluate($event);
        }

        foreach ($result->touchedIcuStayIds as $stayId) {
            $stay = IcuStay::find($stayId);

            if (! $stay) {
                continue;
            }

            $this->cumulativeDoseCalculator->recomputeFor($stay);
            $this->visScoreCalculator->snapshotFor($stay);
            $this->pisRiskEvaluator->evaluate($stay);
        }
    }
}
