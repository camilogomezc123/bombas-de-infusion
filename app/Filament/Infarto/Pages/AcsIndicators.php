<?php

namespace App\Filament\Infarto\Pages;

use App\Services\AcsIndicatorService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class AcsIndicators extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $title = 'Indicadores de SCA e Infarto';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'indicadores';

    protected string $view = 'filament.infarto.indicators';

    public string $year;

    public function mount(): void
    {
        $this->year = now()->format('Y');
    }

    #[Computed]
    public function metrics(): array
    {
        return app(AcsIndicatorService::class)->dashboard($this->year);
    }

    public function goals(): array
    {
        return AcsIndicatorService::GOALS;
    }
}
