<?php

namespace App\Filament\Sepsis\Pages;

use App\Enums\ProgramPermission;
use App\Services\SepsisCostService;
use App\Services\SepsisExecutiveSummaryService;
use App\Services\SepsisIndicatorService;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class MonthlyReport extends Page
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ProgramAccess::can($user, ProgramPermission::ViewIndicators);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Informe mensual';

    protected static ?string $title = 'Informe mensual del Programa de Sepsis';

    protected static ?int $navigationSort = 97;

    protected static ?string $slug = 'informe-mensual';

    protected string $view = 'filament.sepsis.monthly-report';

    public string $year;

    public string $month;

    public function mount(): void
    {
        $this->year = now()->format('Y');
        $this->month = now()->format('Y/m');
    }

    #[Computed]
    public function indicators(): array
    {
        return app(SepsisIndicatorService::class)->dashboard($this->year, $this->month)['current'] ?? [];
    }

    #[Computed]
    public function summary(): array
    {
        return app(SepsisExecutiveSummaryService::class)->summary();
    }

    #[Computed]
    public function cost(): array
    {
        return app(SepsisCostService::class)->summary($this->year, $this->month);
    }

    public function goals(): array
    {
        return SepsisIndicatorService::GOALS;
    }

    public function meetsGoal(string $key, ?float $value): ?bool
    {
        return SepsisIndicatorService::meetsGoal($key, $value);
    }
}
