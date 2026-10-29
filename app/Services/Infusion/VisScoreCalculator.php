<?php

namespace App\Services\Infusion;

use App\Models\DrugInfusionProfile;
use App\Models\IcuStay;
use App\Models\InfusionEvent;
use App\Models\VisScoreSnapshot;
use App\Services\Infusion\Support\DoseRateNormalizer;

/**
 * Calcula el Vasoactive-Inotropic Score (VIS) de una estancia a partir de la tasa activa
 * más reciente de cada fármaco vasoactivo. Pesos estándar (Gaies et al. 2010), sembrados
 * en drug_infusion_profiles.vis_weight: dopamina×1, dobutamina×1, epinefrina×100,
 * norepinefrina×100, milrinona×10, vasopresina×10000 (en U/kg/min). Requiere el último
 * peso registrado de la estancia (IcuStayWeight) — sin peso no se puede calcular y se
 * devuelve null en vez de asumir un valor.
 */
class VisScoreCalculator
{
    public function __construct(private readonly DoseRateNormalizer $normalizer) {}

    public function snapshotFor(IcuStay $icuStay): ?VisScoreSnapshot
    {
        $weight = $icuStay->weights()->latest('recorded_at')->first();

        if (! $weight) {
            return null;
        }

        $weightKg = (float) $weight->weight_kg;
        $breakdown = [];
        $total = 0.0;

        $profiles = DrugInfusionProfile::query()->where('vis_component', true)->get();

        foreach ($profiles as $profile) {
            /** @var InfusionEvent|null $latestEvent */
            $latestEvent = $icuStay->infusionEvents()
                ->where('drug_infusion_profile_id', $profile->id)
                ->latest('occurred_at')
                ->first();

            if (! $latestEvent) {
                continue;
            }

            $rate = $this->normalizer->ratePerKg($latestEvent, $profile->vis_rate_unit, $weightKg);

            if ($rate === null) {
                continue;
            }

            $contribution = $rate * (float) $profile->vis_weight;
            $total += $contribution;
            $breakdown[$profile->name] = [
                'rate' => round($rate, 4),
                'unit' => $profile->vis_rate_unit,
                'weight' => (float) $profile->vis_weight,
                'contribution' => round($contribution, 2),
            ];
        }

        return $icuStay->visScoreSnapshots()->create([
            'computed_at' => now(),
            'vis_score' => round($total, 2),
            'breakdown' => $breakdown,
            'weight_kg_used' => $weightKg,
        ]);
    }
}
