<?php

namespace App\Filament\Sepsis\Pages;

use App\Services\SepsisExecutiveSummaryService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class ExecutiveSummary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Vista general';

    protected static ?string $title = 'Vista general de Sepsis';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = '/';

    protected string $view = 'filament.sepsis.executive-summary';

    #[Computed]
    public function summary(): array
    {
        return app(SepsisExecutiveSummaryService::class)->summary();
    }

    #[Computed]
    public function distributions(): array
    {
        return app(SepsisExecutiveSummaryService::class)->distributions();
    }
}
