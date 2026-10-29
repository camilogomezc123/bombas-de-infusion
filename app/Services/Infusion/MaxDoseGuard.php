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
    /**
     * Si la tasa calculada supera el límite duro por este múltiplo, es más probable un
     * error de unidad/decimal en la bomba o en la concentración capturada (ej. mg
     * reportado como mcg) que una sobredosis real sostenida — confusiones de unidad son
     * una causa documentada de eventos adversos con bombas inteligentes. Se marca aparte
     * para que el equipo verifique el dato en el dispositivo antes de reaccionar como si
     * fuera una dosis real.
     */
    private const IMPLAUSIBILITY_MULTIPLIER = 10.0;

    public function __construct(private readonly DoseRateNormalizer $normalizer) {}

    public function evaluate(InfusionEvent $event): void
    {
        if (! $event->icu_stay_id) {
            return;
        }

        if ($event->isHardLimitOverride()) {
            $this->createAlert($event, 'hard_limit_override', 'critical', $event->drug_infusion_profile_id, sprintf(
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

        if ($rate >= (float) $profile->max_dose_hard * self::IMPLAUSIBILITY_MULTIPLIER) {
            $this->createAlert($event, 'possible_unit_error', 'critical', $profile->id, sprintf(
                'La tasa calculada de %s (%.3f %s) es %.0fx el límite institucional duro (%.3f %s) — revisar la concentración y la tasa '.
                'capturadas en la bomba antes de asumir que es una dosis real: es más probable un error de unidad/decimal que una sobredosis sostenida de esa magnitud.',
                $profile->name, $rate, $profile->dose_limit_unit, $rate / (float) $profile->max_dose_hard, (float) $profile->max_dose_hard, $profile->dose_limit_unit,
            ));
        } elseif ($rate >= (float) $profile->max_dose_hard) {
            $this->createAlert($event, 'max_dose_exceeded', 'critical', $profile->id, sprintf(
                'La tasa calculada de %s (%.3f %s) supera el límite institucional duro (%.3f %s).',
                $profile->name, $rate, $profile->dose_limit_unit, (float) $profile->max_dose_hard, $profile->dose_limit_unit,
            ));
        } elseif ($profile->max_dose_soft && $rate >= (float) $profile->max_dose_soft) {
            $this->createAlert($event, 'max_dose_exceeded', 'warning', $profile->id, sprintf(
                'La tasa calculada de %s (%.3f %s) supera el límite institucional blando (%.3f %s).',
                $profile->name, $rate, $profile->dose_limit_unit, (float) $profile->max_dose_soft, $profile->dose_limit_unit,
            ));
        }
    }

    private function createAlert(InfusionEvent $event, string $type, string $severity, ?int $drugInfusionProfileId, string $message): void
    {
        // Idempotente por evento: si este mismo InfusionEvent ya generó este tipo de
        // alerta (ej. al reprocesarse una ingesta), no duplicar — un evento real nuevo
        // siempre tiene su propio id y sí genera su propia alerta.
        $alreadyExists = InfusionClinicalAlert::query()
            ->where('infusion_event_id', $event->id)
            ->where('type', $type)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        InfusionClinicalAlert::create([
            'icu_stay_id' => $event->icu_stay_id,
            'infusion_event_id' => $event->id,
            'drug_infusion_profile_id' => $drugInfusionProfileId,
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'status' => 'open',
        ]);
    }
}
