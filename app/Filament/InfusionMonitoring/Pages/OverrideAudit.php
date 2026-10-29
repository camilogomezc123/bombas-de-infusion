<?php

namespace App\Filament\InfusionMonitoring\Pages;

use App\Models\InfusionEvent;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

/**
 * Auditoría de gobierno de seguridad de medicación: quién anuló/retrocedió un límite
 * del fabricante o programó fuera de la librería de dosis segura, y con qué frecuencia
 * — no solo la alerta clínica puntual, sino el patrón por operador que una central tipo
 * BD HealthSight reporta para el programa de seguridad de bombas inteligentes.
 */
class OverrideAudit extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $navigationLabel = 'Auditoría de anulaciones';

    protected static ?string $title = 'Auditoría de anulaciones y bypass de librería';

    protected static ?int $navigationSort = 86;

    protected static ?string $slug = 'auditoria-anulaciones';

    protected string $view = 'filament.infusion-monitoring.override-audit';

    #[Computed]
    public function rows(): array
    {
        return InfusionEvent::query()
            ->whereNotNull('overridden_by_operator')
            ->orderByDesc('occurred_at')
            ->get()
            ->groupBy('overridden_by_operator')
            ->map(fn ($events, $operator): array => [
                'operator' => $operator,
                'total' => $events->count(),
                'hard_overrides' => $events->filter(fn (InfusionEvent $e) => $e->isHardLimitOverride())->count(),
                'library_bypasses' => $events->where('used_drug_library', false)->count(),
                'drugs' => $events->pluck('medication_name_raw')->unique()->values()->implode(', '),
                'last_occurred_at' => $events->max('occurred_at'),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
