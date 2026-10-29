<?php

namespace App\Services\Infusion;

use App\Models\DrugInfusionProfile;
use App\Models\IcuStay;
use App\Models\InfusionEvent;
use App\Models\PumpDevice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Normaliza PumpEventData (de cualquier conector) a InfusionEvent, resolviendo bomba,
 * estancia y perfil de fármaco. La resolución de la estancia se hace contra
 * PumpDevice.current_bed_label (no contra el área cruda del evento): el área que reporta
 * la bomba es un texto libre poco confiable para enlazar al paciente, mientras que el
 * mapeo bomba↔cama es un dato que el propio equipo puede corregir en PumpDeviceResource.
 */
class InfusionEventIngestionService
{
    public function ingest(iterable $pumpEvents): IngestionResult
    {
        $events = new Collection;
        $touchedStayIds = [];

        foreach ($pumpEvents as $data) {
            $pumpDevice = $this->resolvePumpDevice($data);
            $icuStay = $this->resolveIcuStay($pumpDevice, $data->occurredAt);
            $profile = DrugInfusionProfile::matchByRawName($data->medicationNameRaw);

            $event = InfusionEvent::updateOrCreate(
                [
                    'vendor_infusion_id' => $data->vendorInfusionId,
                    'occurred_at' => $data->occurredAt,
                ],
                [
                    'pump_device_id' => $pumpDevice->id,
                    'icu_stay_id' => $icuStay?->id,
                    'drug_infusion_profile_id' => $profile?->id,
                    'medication_name_raw' => $data->medicationNameRaw,
                    'concentration_raw' => $data->concentrationRaw,
                    'limit_type' => $data->limitType,
                    'alert_level' => $data->alertLevel,
                    'next_action' => $data->nextAction,
                    'care_area' => $data->careArea,
                    'attempted_value' => $data->attemptedValue,
                    'attempted_unit' => $data->attemptedUnit,
                    'soft_limit_low' => $data->softLimitLow,
                    'soft_limit_high' => $data->softLimitHigh,
                    'hard_limit_low' => $data->hardLimitLow,
                    'hard_limit_high' => $data->hardLimitHigh,
                    'existing_dose_rate_value' => $data->existingDoseRateValue,
                    'existing_dose_rate_unit' => $data->existingDoseRateUnit,
                    'next_drug' => $data->nextDrug,
                    'raw_payload' => $data->rawPayload,
                    'ingested_at' => now(),
                ],
            );

            $events->push($event);

            if ($icuStay) {
                $touchedStayIds[$icuStay->id] = $icuStay->id;
            }
        }

        return new IngestionResult($events, array_values($touchedStayIds));
    }

    private function resolvePumpDevice(PumpEventData $data): PumpDevice
    {
        $device = PumpDevice::firstOrNew(['hospital_device_id' => $data->hospitalDeviceId]);
        $isNew = ! $device->exists;

        $device->fill([
            'serial_number' => $data->serialNumber ?? $device->serial_number,
            'model' => $data->model ?? $device->model,
            'device_group' => $data->deviceGroup ?? $device->device_group,
            'is_active' => true,
        ]);

        if ($isNew) {
            $device->current_bed_label = $data->careArea;
        }

        $device->save();

        return $device;
    }

    private function resolveIcuStay(PumpDevice $pumpDevice, CarbonImmutable $occurredAt): ?IcuStay
    {
        if (! $pumpDevice->current_bed_label) {
            return null;
        }

        return IcuStay::query()
            ->where('bed_label', $pumpDevice->current_bed_label)
            ->where('admission_at', '<=', $occurredAt)
            ->where(fn ($q) => $q->whereNull('icu_discharge_at')->orWhere('icu_discharge_at', '>=', $occurredAt))
            ->orderByDesc('admission_at')
            ->first();
    }
}
