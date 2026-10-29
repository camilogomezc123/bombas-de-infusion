<?php

namespace App\Filament\InfusionMonitoring\Pages;

use App\Services\InfusionIndicatorService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class InfusionIndicators extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $title = 'Indicadores de Monitoreo de Bombas';

    protected static ?int $navigationSort = 85;

    protected static ?string $slug = 'indicadores';

    protected string $view = 'filament.infusion-monitoring.indicators';

    #[Computed]
    public function metrics(): array
    {
        return app(InfusionIndicatorService::class)->summary();
    }
}
