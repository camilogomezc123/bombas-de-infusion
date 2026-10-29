<?php

namespace App\Filament\IcuLiberation\Pages;

use App\Services\IcuLiberationIndicatorService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class IcuLiberationIndicators extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $title = 'Indicadores ICU Liberation';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'indicadores';

    protected string $view = 'filament.icu-liberation.indicators';

    public string $year;

    public function mount(): void
    {
        $this->year = now()->format('Y');
    }

    #[Computed]
    public function metrics(): array
    {
        return app(IcuLiberationIndicatorService::class)->dashboard($this->year);
    }

    public function goals(): array
    {
        return IcuLiberationIndicatorService::GOALS;
    }
}
