<?php

namespace App\Services\Infusion;

use App\Models\InfusionClinicalAlert;
use App\Models\InfusionEvent;
use App\Services\Infusion\Support\DoseRateNormalizer;

/**
 * Genera alertas cuando un InfusionEvent recién ingresado (a) corresponde a un límite
 * duro que el propio fabricante ya marcó como anulado/retrocedido, o (b) excede el
 * umbral institucional propio configurado en DrugInfusionProfile (independiente del
 * DERS del fabricante, que puede no reflejar el protocolo de la institución).
 */
class MaxDoseGuard
{
    public function __construct(private readonly DoseRateNormalizer $normalizer) {}

    public function evaluate(InfusionEvent $event): void
    {
        if (! $event->icu_stay_id) {
            return;
        }

        if ($event->isHardLimitOverride()) {
            $this->createAlert($event, 'hard_limit_override', 'critical', sprintf(
                "El fabricante registró un límite duro (%s) con acción '%s' para %s (intento: %s %s).",
                $event->alert_level,
                $event->next_action,
                $event->medication_name_raw,
                $event->attempted_value,
                $event->attempted_unit,
            ));
        }

        $profile = $event->drugInfusionProfile;

        if (! $profile || ! $profile->max_dose_hard || ! $event->icuStay) {
            return;
        }

        $weight = $event->icuStay->weights()->latest('recorded_at')->first();

        if (! $weight) {
            return;
        }

        $rate = $this->normalizer->ratePerKg($event, $profile->dose_limit_unit ?? '', (float) $weight->weight_kg);

        if ($rate === null) {
            return;
        }

        if ($rate >= (float) $profile->max_dose_hard) {
            $this->createAlert($event, 'max_dose_exceeded', 'critical', sprintf(
                'La tasa calculada de %s (%.3f %s) supera el límite institucional duro (%.3f %s).',
                $profile->name, $rate, $profile->dose_limit_unit, (float) $profile->max_dose_hard, $profile->dose_limit_unit,
            ));
        } elseif ($profile->max_dose_soft && $rate >= (float) $profile->max_dose_soft) {
            $this->createAlert($event, 'max_dose_exceeded', 'warning', sprintf(
                'La tasa calculada de %s (%.3f %s) supera el límite institucional blando (%.3f %s).',
                $profile->name, $rate, $profile->dose_limit_unit, (float) $profile->max_dose_soft, $profile->dose_limit_unit,
            ));
        }
    }

    private function createAlert(InfusionEvent $event, string $type, string $severity, string $message): void
    {
        InfusionClinicalAlert::create([
            'icu_stay_id' => $event->icu_stay_id,
            'infusion_event_id' => $event->id,
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'status' => 'open',
        ]);
    }
}
