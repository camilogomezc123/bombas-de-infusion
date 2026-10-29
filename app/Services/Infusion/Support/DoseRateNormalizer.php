<?php

namespace App\Services\Infusion\Support;

use App\Models\InfusionEvent;

/**
 * Convierte la tasa cruda de un InfusionEvent (que puede venir en mL/h o directamente en
 * unidad de fármaco/h, según cómo la reporte la bomba) a una tasa por kilogramo en la
 * unidad clínica de referencia del perfil (mcg/kg/min, units/kg/min o mg/kg/h).
 *
 * Es un cálculo best-effort sobre texto libre de fabricante (ej. "20 Units / 100 ml"):
 * si la concentración o la tasa no se pueden interpretar, devuelve null en vez de
 * adivinar — el llamador debe tratar null como "dato insuficiente", no como cero.
 */
class DoseRateNormalizer
{
    private const MASS_TO_MCG = [
        'mcg' => 1.0,
        'mg' => 1000.0,
        'g' => 1_000_000.0,
    ];

    /**
     * @return float|null tasa en la unidad objetivo (mcg/kg/min, units/kg/min o mg/kg/h)
     */
    public function ratePerKg(InfusionEvent $event, string $targetUnit, float $weightKg): ?float
    {
        if ($weightKg <= 0 || $event->existing_dose_rate_value === null) {
            return null;
        }

        $perMinuteMass = $this->massPerMinute($event);

        if ($perMinuteMass === null) {
            return null;
        }

        [$massPerMinute, $massKind] = $perMinuteMass;

        return match ($targetUnit) {
            'mcg/kg/min' => $massKind === 'mcg' ? $massPerMinute / $weightKg : null,
            'units/kg/min' => $massKind === 'units' ? $massPerMinute / $weightKg : null,
            'mg/kg/h' => $massKind === 'mcg' ? ($massPerMinute * 60 / 1000) / $weightKg : null,
            default => null,
        };
    }

    /**
     * Masa (o unidades) por minuto que entrega la bomba en este evento, sin dividir por
     * peso — usado tanto por ratePerKg() como por el cálculo de dosis acumulada.
     *
     * @return array{0: float, 1: 'mcg'|'units'}|null
     */
    public function massPerMinute(InfusionEvent $event): ?array
    {
        $rateUnit = mb_strtolower((string) $event->existing_dose_rate_unit);
        $rateValue = (float) $event->existing_dose_rate_value;

        // La bomba ya reporta masa/tiempo (ej. "units/hr", "mcg/hr", "mg/hr").
        if (preg_match('/^(mcg|mg|g|units)\/hr$/', $rateUnit, $m)) {
            if ($m[1] === 'units') {
                return [$rateValue / 60, 'units'];
            }

            return [($rateValue * self::MASS_TO_MCG[$m[1]]) / 60, 'mcg'];
        }

        // La bomba reporta volumen/tiempo (ej. "ml/hr") — hay que pasar por la
        // concentración para obtener masa/tiempo.
        if ($rateUnit === 'ml/hr' && $event->concentration_raw) {
            $concentration = $this->parseConcentration($event->concentration_raw);

            if ($concentration === null) {
                return null;
            }

            [$amount, $kind, $volumeMl] = $concentration;
            $amountPerMl = $amount / $volumeMl;
            $amountPerHour = $amountPerMl * $rateValue;

            if ($kind === 'units') {
                return [$amountPerHour / 60, 'units'];
            }

            return [($amountPerHour * self::MASS_TO_MCG[$kind]) / 60, 'mcg'];
        }

        return null;
    }

    /**
     * Interpreta textos tipo "20 Units / 100 ml" o "4 mg / 100 ml".
     *
     * @return array{0: float, 1: 'mcg'|'mg'|'g'|'units', 2: float}|null
     */
    private function parseConcentration(string $raw): ?array
    {
        if (! preg_match('/([\d.]+)\s*(mcg|mg|g|units)\s*\/\s*([\d.]+)\s*ml/i', $raw, $m)) {
            return null;
        }

        return [(float) $m[1], mb_strtolower($m[2]), (float) $m[3]];
    }
}
