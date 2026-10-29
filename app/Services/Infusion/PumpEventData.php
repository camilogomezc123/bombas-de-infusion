<?php

namespace App\Services\Infusion;

use Carbon\CarbonImmutable;

/**
 * Forma normalizada de un evento de bomba, independiente del conector que lo produjo
 * (fixture, API o base de datos del fabricante). Los nombres de propiedades siguen las
 * columnas vistas en el reporte del fabricante (ID del dispositivo, área, medicamento,
 * concentración, tasa, alertas DERS, límites blando/duro, próxima acción).
 */
readonly class PumpEventData
{
    public function __construct(
        public string $hospitalDeviceId,
        public ?string $serialNumber,
        public ?string $model,
        public ?string $deviceGroup,
        public ?string $careArea,
        public ?string $vendorInfusionId,
        public CarbonImmutable $occurredAt,
        public string $medicationNameRaw,
        public ?string $concentrationRaw,
        public ?string $limitType,
        public ?string $alertLevel,
        public ?string $nextAction,
        public ?float $attemptedValue,
        public ?string $attemptedUnit,
        public ?float $softLimitLow,
        public ?float $softLimitHigh,
        public ?float $hardLimitLow,
        public ?float $hardLimitHigh,
        public ?float $existingDoseRateValue,
        public ?string $existingDoseRateUnit,
        public ?string $nextDrug,
        public array $rawPayload = [],
        public int $channelNumber = 1,
        public ?string $deviceStatus = null,
        public ?int $batteryPct = null,
        public ?float $volumeRemainingMl = null,
        public ?int $infusionDurationPlannedMinutes = null,
        public bool $usedDrugLibrary = true,
        public ?string $overriddenByOperator = null,
    ) {}
}
