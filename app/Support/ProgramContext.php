<?php

namespace App\Support;

use App\Models\ClinicalProgram;

/**
 * Contexto institucional compartido por todos los centros.
 *
 * Los recursos de gobierno y calidad consultan este contexto en vez de conocer
 * detalles de Sepsis o Infarto.
 */
class ProgramContext
{
    public const PANEL_CODES = [
        'sepsis' => 'SEPSIS',
        'infarto' => 'INFARTO',
        'tep' => 'TEP',
        'icu-liberation' => 'ICULIB',
        'pics' => 'PICS',
        'infusion-monitoring' => 'BOMBAS',
    ];

    public function code(?string $code = null): string
    {
        if (filled($code)) {
            return mb_strtoupper($code);
        }

        $panelId = filament()->getCurrentPanel()?->getId();

        return self::PANEL_CODES[$panelId] ?? 'SEPSIS';
    }

    public function program(?string $code = null): ?ClinicalProgram
    {
        return ClinicalProgram::query()
            ->whereRaw('upper(code) = ?', [$this->code($code)])
            ->where('is_active', true)
            ->first();
    }
}
