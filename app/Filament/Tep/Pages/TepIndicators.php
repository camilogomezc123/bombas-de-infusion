<?php

namespace App\Filament\Tep\Pages;

use App\Services\TepIndicatorService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class TepIndicators extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $title = 'Indicadores de TEP';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'indicadores';

    protected string $view = 'filament.tep.indicators';

    public string $year;

    public function mount(): void
    {
        $this->year = now()->format('Y');
    }

    #[Computed]
    public function metrics(): array
    {
        return app(TepIndicatorService::class)->dashboard($this->year);
    }

    public function goals(): array
    {
        return TepIndicatorService::GOALS;
    }
}
