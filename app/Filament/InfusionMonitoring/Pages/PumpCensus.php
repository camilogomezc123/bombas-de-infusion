<?php

namespace App\Filament\InfusionMonitoring\Pages;

use App\Models\IcuStay;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

/**
 * Censo por cubículo/cama de las estancias activas con bomba asociada: último
 * medicamento infundido, score VIS más reciente (y su tendencia frente al cálculo
 * anterior) y alertas clínicas abiertas. No sustituye el monitoreo directo del paciente.
 */
class PumpCensus extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Censo de bombas';

    protected static ?string $title = 'Censo de bombas';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'censo';

    protected string $view = 'filament.infusion-monitoring.census';

    #[Computed]
    public function rows(): array
    {
        return IcuStay::query()
            ->where('status', 'active')
            ->where('is_cancelled', false)
            ->with([
                'patient', 'icuUnit',
                'infusionEvents' => fn ($q) => $q->latest('occurred_at')->limit(5),
                'visScoreSnapshots' => fn ($q) => $q->latest('computed_at')->limit(2),
                'infusionClinicalAlerts' => fn ($q) => $q->where('status', '!=', 'resolved'),
            ])
            ->orderBy('bed_label')
            ->get()
            ->filter(fn (IcuStay $stay) => $stay->infusionEvents->isNotEmpty() || $stay->visScoreSnapshots->isNotEmpty())
            ->map(fn (IcuStay $stay): array => [
                'stay' => $stay,
                'last_event' => $stay->infusionEvents->first(),
                'active_drugs' => $stay->infusionEvents->pluck('medication_name_raw')->unique()->values(),
                'vis_current' => $stay->visScoreSnapshots->first(),
                'vis_previous' => $stay->visScoreSnapshots->skip(1)->first(),
                'open_alerts' => $stay->infusionClinicalAlerts,
            ])
            ->values()
            ->all();
    }
}
