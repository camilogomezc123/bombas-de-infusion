<?php

namespace App\Services\Infusion;

use App\Models\DrugInfusionProfile;
use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;
use App\Services\Infusion\Support\DoseRateNormalizer;

/**
 * Screening de riesgo de síndrome de infusión por propofol (PIS/PRIS): marca riesgo
 * cuando la tasa calculada en mg/kg/h se sostiene por encima de
 * drug_infusion_profiles.pis_dose_threshold durante al menos
 * pis_duration_threshold_minutes de forma contigua. Esto es un tamizaje por dosis y
 * duración, NO un diagnóstico — el diagnóstico de PRIS requiere correlación con CK,
 * triglicéridos, lactato y gases arteriales, datos que no provienen de la bomba. El
 * mensaje de la alerta debe dejarlo explícito.
 */
class PisRiskEvaluator
{
    public function __construct(private readonly DoseRateNormalizer $normalizer) {}

    public function evaluate(IcuStay $icuStay): void
    {
        $profiles = DrugInfusionProfile::query()->where('pis_risk_applicable', true)->get();

        foreach ($profiles as $profile) {
            $this->evaluateForProfile($icuStay, $profile);
        }
    }

    private function evaluateForProfile(IcuStay $icuStay, DrugInfusionProfile $profile): void
    {
        if (! $profile->pis_dose_threshold || ! $profile->pis_duration_threshold_minutes) {
            return;
        }

        $weight = $icuStay->weights()->latest('recorded_at')->first();

        if (! $weight) {
            return;
        }

        $alreadyOpen = $icuStay->infusionClinicalAlerts()
            ->where('type', 'pis_risk')
            ->where('status', '!=', 'resolved')
            ->exists();

        if ($alreadyOpen) {
            return;
        }

        $events = $icuStay->infusionEvents()
            ->where('drug_infusion_profile_id', $profile->id)
            ->orderBy('occurred_at')
            ->get();

        $sustainedMinutes = 0.0;
        $weightKg = (float) $weight->weight_kg;

        for ($i = 0; $i < $events->count() - 1; $i++) {
            $current = $events[$i];
            $next = $events[$i + 1];
            $rate = $this->normalizer->ratePerKg($current, 'mg/kg/h', $weightKg);

            if ($rate === null || $rate < (float) $profile->pis_dose_threshold) {
                $sustainedMinutes = 0.0;

                continue;
            }

            $sustainedMinutes += $current->occurred_at->diffInMinutes($next->occurred_at);

            if ($sustainedMinutes >= $profile->pis_duration_threshold_minutes) {
                InfusionClinicalAlert::create([
                    'icu_stay_id' => $icuStay->id,
                    'infusion_event_id' => $next->id,
                    'type' => 'pis_risk',
                    'severity' => 'warning',
                    'message' => sprintf(
                        'Tamizaje de riesgo (no diagnóstico): %s sostenido ≥ %.1f mg/kg/h por %d minutos o más. '.
                        'Correlacionar con CK, triglicéridos, lactato y gases arteriales antes de concluir síndrome de infusión por propofol.',
                        $profile->name, (float) $profile->pis_dose_threshold, (int) $sustainedMinutes,
                    ),
                    'status' => 'open',
                ]);

                return;
            }
        }
    }
}
