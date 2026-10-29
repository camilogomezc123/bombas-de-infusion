<?php

namespace App\Filament\Pics\Pages;

use App\Services\PicsIndicatorService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class PicsIndicators extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $title = 'Indicadores PICS';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'indicadores';

    protected string $view = 'filament.pics.indicators';

    public string $year;

    public function mount(): void
    {
        $this->year = now()->format('Y');
    }

    #[Computed]
    public function metrics(): array
    {
        return app(PicsIndicatorService::class)->dashboard($this->year);
    }

    public function goals(): array
    {
        return PicsIndicatorService::GOALS;
    }
}
