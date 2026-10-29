<?php

namespace App\Enums;

/**
 * Etapa clínica del episodio (UCI → Hospitalización → Egreso → Seguimiento) — distinta
 * de CaseStatus, que representa el flujo de auditoría/revisión del caso, no la evolución
 * clínica real del paciente.
 */
enum ClinicalStage: string
{
    case Uci = 'uci';
    case Hospitalizacion = 'hospitalizacion';
    case Egreso = 'egreso';
    case Seguimiento = 'seguimiento';

    public function label(): string
    {
        return match ($this) {
            self::Uci => 'UCI',
            self::Hospitalizacion => 'Hospitalización',
            self::Egreso => 'Egreso',
            self::Seguimiento => 'Seguimiento',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Uci => 0,
            self::Hospitalizacion => 1,
            self::Egreso => 2,
            self::Seguimiento => 3,
        };
    }

    /**
     * @return array<int, self>
     */
    public static function sequence(): array
    {
        return [self::Uci, self::Hospitalizacion, self::Egreso, self::Seguimiento];
    }
}
