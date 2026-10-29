<?php

namespace App\Services\Infusion\Connectors;

use App\Services\Infusion\Contracts\PumpDataConnector;
use App\Services\Infusion\PumpEventData;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Conector por defecto mientras no se confirme el mecanismo real de BD HealthSight /
 * Systems Manager (API REST vs. acceso directo a base de datos). Devuelve eventos de
 * ejemplo con la forma exacta de los reportes compartidos (bitácora de Vasopresina con
 * alertas DERS/PUMP Blando/Duro, y el evento de Ondansetrón con el detalle expandido),
 * lo que permite construir y probar el resto del pipeline hoy mismo. Sustituir el
 * binding en config('services.infusion_pumps.driver') por el conector real cuando el
 * mecanismo quede confirmado.
 */
class FixturePumpConnector implements PumpDataConnector
{
    public function fetchEvents(CarbonInterface $since): iterable
    {
        foreach ($this->events() as $event) {
            if ($event->occurredAt->greaterThan($since)) {
                yield $event;
            }
        }
    }

    /**
     * @return array<int, PumpEventData>
     */
    private function events(): array
    {
        $vasopresinaDevice = [
            'hospitalDeviceId' => 'TB_UCIP6_9003511',
            'serialNumber' => '30501411',
            'model' => 'EVO LVP',
            'deviceGroup' => 'Adultos',
            'careArea' => 'UCI ADULTOS',
        ];

        $vasopresinaRows = [
            ['2026-10-22 17:27:53', 'DERS - Blando', 'Anulaciones', 1, 2, 3],
            ['2026-10-20 14:37:36', 'DERS - Blando', 'Anulaciones', 4, 2, 3],
            ['2026-10-19 15:43:59', 'DERS - Blando', 'Anulaciones', 0.1, 2, 3],
            ['2026-10-18 10:08:11', 'DERS - Blando', 'Anulaciones', 0.05, 2, 3],
            ['2026-10-18 10:07:25', 'DERS - Blando', 'Anulaciones', 0.05, 2, 3],
            ['2026-10-18 10:04:24', 'DERS - Blando', 'Anulaciones', 0.05, 2, 3],
            ['2026-10-17 19:48:08', 'DERS - Blando', 'Anulaciones', 4, 2, 3],
        ];

        $events = [];
        foreach ($vasopresinaRows as [$occurredAt, $alertLevel, $nextAction, $attempted, $softLow, $softHigh]) {
            $events[] = new PumpEventData(
                hospitalDeviceId: $vasopresinaDevice['hospitalDeviceId'],
                serialNumber: $vasopresinaDevice['serialNumber'],
                model: $vasopresinaDevice['model'],
                deviceGroup: $vasopresinaDevice['deviceGroup'],
                careArea: $vasopresinaDevice['careArea'],
                vendorInfusionId: $vasopresinaDevice['hospitalDeviceId'].'-'.str_replace([' ', ':', '-'], '', $occurredAt),
                occurredAt: CarbonImmutable::parse($occurredAt),
                medicationNameRaw: 'Vasopresina',
                concentrationRaw: '20 Units / 100 ml',
                limitType: 'Tasa de dosis',
                alertLevel: $alertLevel,
                nextAction: $nextAction,
                attemptedValue: $attempted,
                attemptedUnit: 'units/hr',
                softLimitLow: $softLow,
                softLimitHigh: $softHigh,
                hardLimitLow: null,
                hardLimitHigh: null,
                existingDoseRateValue: $attempted,
                existingDoseRateUnit: 'units/hr',
                nextDrug: null,
                rawPayload: ['source' => 'fixture', 'row' => $occurredAt],
                channelNumber: 1,
                deviceStatus: 'infusing',
                batteryPct: 78,
                usedDrugLibrary: true,
                overriddenByOperator: 'ENF-1042',
            );
        }

        // Canal 2 de la misma bomba (modelo EVO LVP, 2 canales): una segunda línea
        // vasoactiva concurrente — así se ve en el canal multicanal de una central real,
        // no solo "el último evento del dispositivo".
        foreach ([
            ['2026-10-28 07:30:00', 110],
            ['2026-10-28 15:00:00', 95],
        ] as [$occurredAt, $attempted]) {
            $events[] = new PumpEventData(
                hospitalDeviceId: $vasopresinaDevice['hospitalDeviceId'],
                serialNumber: $vasopresinaDevice['serialNumber'],
                model: $vasopresinaDevice['model'],
                deviceGroup: $vasopresinaDevice['deviceGroup'],
                careArea: $vasopresinaDevice['careArea'],
                vendorInfusionId: $vasopresinaDevice['hospitalDeviceId'].'-CH2-'.str_replace([' ', ':', '-'], '', $occurredAt),
                occurredAt: CarbonImmutable::parse($occurredAt),
                medicationNameRaw: 'Norepinefrina',
                concentrationRaw: '4 mg / 250 ml',
                limitType: 'Tasa de dosis',
                alertLevel: null,
                nextAction: null,
                attemptedValue: $attempted,
                attemptedUnit: 'mcg/hr',
                softLimitLow: null,
                softLimitHigh: null,
                hardLimitLow: null,
                hardLimitHigh: null,
                existingDoseRateValue: $attempted,
                existingDoseRateUnit: 'mcg/hr',
                nextDrug: null,
                rawPayload: ['source' => 'fixture', 'row' => $occurredAt],
                channelNumber: 2,
                deviceStatus: 'infusing',
                batteryPct: 78,
                volumeRemainingMl: 180,
                infusionDurationPlannedMinutes: 492,
                usedDrugLibrary: true,
            );
        }

        // Mismo dispositivo, pero tres eventos "PUMP - Duro / Retrocesos" en mL/hr, tal
        // como aparecen en la captura (la bomba reporta a veces en la unidad de volumen
        // en vez de la unidad del fármaco).
        foreach ([
            ['2026-10-18 10:04:17', 0.01],
            ['2026-10-18 10:04:08', 0.05],
            ['2026-10-18 10:03:57', 0.05],
        ] as [$occurredAt, $attempted]) {
            $events[] = new PumpEventData(
                hospitalDeviceId: $vasopresinaDevice['hospitalDeviceId'],
                serialNumber: $vasopresinaDevice['serialNumber'],
                model: $vasopresinaDevice['model'],
                deviceGroup: $vasopresinaDevice['deviceGroup'],
                careArea: $vasopresinaDevice['careArea'],
                vendorInfusionId: $vasopresinaDevice['hospitalDeviceId'].'-'.str_replace([' ', ':', '-'], '', $occurredAt),
                occurredAt: CarbonImmutable::parse($occurredAt),
                medicationNameRaw: 'Vasopresina',
                concentrationRaw: '20 Units / 100 ml',
                limitType: 'Tasa de dosis',
                alertLevel: 'PUMP - Duro',
                nextAction: 'Retrocesos',
                attemptedValue: $attempted,
                attemptedUnit: 'ml/hr',
                softLimitLow: null,
                softLimitHigh: null,
                hardLimitLow: 0.1,
                hardLimitHigh: 1200,
                existingDoseRateValue: $attempted,
                existingDoseRateUnit: 'ml/hr',
                nextDrug: null,
                rawPayload: ['source' => 'fixture', 'row' => $occurredAt],
                channelNumber: 1,
                deviceStatus: 'occlusion',
                batteryPct: 78,
                usedDrugLibrary: false,
                overriddenByOperator: 'ENF-1042',
            );
        }

        $events[] = new PumpEventData(
            hospitalDeviceId: 'TB_UCIP6_9003566',
            serialNumber: '30501418',
            model: 'EVO LVP',
            deviceGroup: 'Adultos',
            careArea: 'UCI ADULTOS',
            vendorInfusionId: '305014182061028134354',
            occurredAt: CarbonImmutable::parse('2026-10-28 08:44:06'),
            medicationNameRaw: 'Ondansetron',
            concentrationRaw: null,
            limitType: 'Tasa de dosis',
            alertLevel: 'DERS - Blando',
            nextAction: 'Anulaciones',
            attemptedValue: 220,
            attemptedUnit: 'ml/hr',
            softLimitLow: 50,
            softLimitHigh: 201,
            hardLimitLow: null,
            hardLimitHigh: null,
            existingDoseRateValue: null,
            existingDoseRateUnit: null,
            nextDrug: 'Hioscina',
            rawPayload: ['source' => 'fixture', 'row' => '2026-10-28 08:44:06'],
            channelNumber: 1,
            deviceStatus: 'battery_low',
            batteryPct: 18,
            volumeRemainingMl: 40,
            infusionDurationPlannedMinutes: 11,
            usedDrugLibrary: false,
            overriddenByOperator: 'ENF-2089',
        );

        return $events;
    }
}
