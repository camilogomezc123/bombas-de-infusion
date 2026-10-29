<?php

namespace App\Services\Infusion;

use App\Models\InfusionClinicalAlert;
use App\Models\PumpDevice;
use App\Models\InfusionEvent;

/**
 * Alertas por estado operativo del dispositivo (oclusión, aire en línea, batería baja)
 * — la telemetría que una central tipo Fresenius Agilia Link+/B.Braun OnlineSuite ya
 * muestra en vivo junto al dato clínico, y que antes solo quedaba en el campo
 * `device_status` del evento sin generar ninguna alerta accionable para enfermería.
 */
class DeviceAlarmGuard
{
    private const SEVERITY_BY_STATUS = [
        'occlusion' => 'critical',
        'air_in_line' => 'critical',
        'battery_low' => 'warning',
    ];

    public function evaluate(InfusionEvent $event): void
    {
        if (! $event->icu_stay_id || ! $event->device_status) {
            return;
        }

        $severity = self::SEVERITY_BY_STATUS[$event->device_status] ?? null;

        if (! $severity) {
            return;
        }

        $label = PumpDevice::STATUSES[$event->device_status] ?? $event->device_status;

        $alreadyOpen = InfusionClinicalAlert::query()
            ->where('icu_stay_id', $event->icu_stay_id)
            ->where('type', 'device_alarm')
            ->where('status', '!=', 'resolved')
            ->where('message', 'like', '%'.$label.'%')
            ->exists();

        if ($alreadyOpen) {
            return;
        }

        InfusionClinicalAlert::create([
            'icu_stay_id' => $event->icu_stay_id,
            'infusion_event_id' => $event->id,
            'drug_infusion_profile_id' => $event->drug_infusion_profile_id,
            'type' => 'device_alarm',
            'severity' => $severity,
            'message' => sprintf(
                '%s (canal %d) reporta %s en la infusión de %s.',
                $event->pumpDevice?->hospital_device_id ?? 'La bomba',
                $event->channel_number,
                $label,
                $event->medication_name_raw,
            ),
            'status' => 'open',
        ]);
    }
}
