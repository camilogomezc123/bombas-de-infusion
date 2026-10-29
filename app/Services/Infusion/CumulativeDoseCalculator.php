<?php

namespace App\Services\Infusion;

use App\Models\DrugInfusionProfile;
use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;
use App\Models\InfusionDoseSummary;
use App\Services\Infusion\Support\DoseRateNormalizer;
use Illuminate\Support\Carbon;

/**
 * Reconstruye la dosis acumulada por fármaco y por día de una estancia a partir de la
 * serie de InfusionEvent. Cada evento es una muestra puntual (alerta/cambio de tasa), no
 * telemetría continua, así que se asume que la tasa reportada en un evento estuvo vigente
 * hasta el siguiente evento del mismo fármaco — no se extrapola la última tasa conocida
 * más allá del último evento observado, para no sobreestimar en silencio.
 */
class CumulativeDoseCalculator
{
    public function __construct(private readonly DoseRateNormalizer $normalizer) {}

    public function recomputeFor(IcuStay $icuStay): void
    {
        $profileIds = $icuStay->infusionEvents()
            ->whereNotNull('drug_infusion_profile_id')
            ->distinct()
            ->pluck('drug_infusion_profile_id');

        foreach ($profileIds as $profileId) {
            $profile = DrugInfusionProfile::find($profileId);

            if ($profile) {
                $this->recomputeForDrug($icuStay, $profile);
            }
        }
    }

    private function recomputeForDrug(IcuStay $icuStay, DrugInfusionProfile $profile): void
    {
        $events = $icuStay->infusionEvents()
            ->where('drug_infusion_profile_id', $profile->id)
            ->orderBy('occurred_at')
            ->get();

        InfusionDoseSummary::query()
            ->where('icu_stay_id', $icuStay->id)
            ->where('drug_infusion_profile_id', $profile->id)
            ->delete();

        /** @var array<string, array{amount: float, kind: string, max_rate: float}> $byDate */
        $byDate = [];

        for ($i = 0; $i < $events->count() - 1; $i++) {
            $current = $events[$i];
            $next = $events[$i + 1];

            $perMinute = $this->normalizer->massPerMinute($current);

            if ($perMinute === null) {
                continue;
            }

            [$rate, $kind] = $perMinute;

            $this->accumulateInterval($byDate, $current->occurred_at, $next->occurred_at, $rate, $kind);
        }

        $lastDate = null;
        $lastCumulativeDose = null;

        foreach ($byDate as $date => $data) {
            $isUnits = $data['kind'] === 'units';
            $cumulativeDose = round($isUnits ? $data['amount'] : $data['amount'] / 1000, 4);

            InfusionDoseSummary::create([
                'icu_stay_id' => $icuStay->id,
                'drug_infusion_profile_id' => $profile->id,
                'summary_date' => $date,
                'cumulative_dose' => $cumulativeDose,
                'dose_unit' => $isUnits ? 'units' : 'mg',
                'max_rate_seen' => round($data['max_rate'], 4),
                'max_rate_unit' => $isUnits ? 'units/min' : 'mcg/min',
                'computed_at' => now(),
            ]);

            $lastDate = $date;
            $lastCumulativeDose = $cumulativeDose;
        }

        if ($lastDate !== null) {
            $this->checkCumulativeThreshold($icuStay, $profile, $lastDate, $lastCumulativeDose);
        }
    }

    /**
     * Compara el total acumulado del día más reciente contra el umbral institucional
     * (mg o unidades por kilogramo por día, configurado en DrugInfusionProfile). Solo
     * evalúa el día más reciente con eventos — es el dato accionable para la decisión
     * clínica de hoy.
     */
    private function checkCumulativeThreshold(IcuStay $icuStay, DrugInfusionProfile $profile, string $date, float $cumulativeDose): void
    {
        if (! $profile->max_cumulative_dose_per_kg_per_day) {
            return;
        }

        $weight = $icuStay->weights()->latest('recorded_at')->first();

        if (! $weight || (float) $weight->weight_kg <= 0) {
            return;
        }

        $perKg = $cumulativeDose / (float) $weight->weight_kg;

        if ($perKg < (float) $profile->max_cumulative_dose_per_kg_per_day) {
            return;
        }

        InfusionClinicalAlert::create([
            'icu_stay_id' => $icuStay->id,
            'drug_infusion_profile_id' => $profile->id,
            'type' => 'max_dose_exceeded',
            'severity' => 'critical',
            'message' => sprintf(
                'Dosis acumulada de %s en %s: %.3f %s/kg (umbral institucional: %.3f %s/kg/día).',
                $profile->name, $date, $perKg, $profile->dose_unit, (float) $profile->max_cumulative_dose_per_kg_per_day, $profile->dose_unit,
            ),
            'status' => 'open',
        ]);
    }

    /**
     * @param  array<string, array{amount: float, kind: string, max_rate: float}>  $byDate
     */
    private function accumulateInterval(array &$byDate, Carbon $start, Carbon $end, float $ratePerMinute, string $kind): void
    {
        $cursor = $start->copy();

        while ($cursor->lessThan($end)) {
            $dayEnd = $cursor->copy()->startOfDay()->addDay();
            $segmentEnd = $dayEnd->lessThan($end) ? $dayEnd : $end;
            $minutes = $cursor->diffInSeconds($segmentEnd) / 60;

            $date = $cursor->toDateString();
            $byDate[$date] ??= ['amount' => 0.0, 'kind' => $kind, 'max_rate' => 0.0];
            $byDate[$date]['amount'] += $ratePerMinute * $minutes;
            $byDate[$date]['max_rate'] = max($byDate[$date]['max_rate'], $ratePerMinute);

            $cursor = $segmentEnd;
        }
    }
}
