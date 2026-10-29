<?php

namespace App\Filament\Sepsis\Pages;

use App\Services\SepsisCostService;
use App\Services\SepsisIndicatorService;
use App\Services\SepsisSeverityService;
use App\Services\SepsisSourceControlTimingService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class SepsisIndicators extends Page
{
    public const ALL = 'all';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $title = 'Indicadores Código Sepsis';

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'indicadores';

    protected string $view = 'filament.sepsis.indicators';

    public string $year = '';

    public string $month = self::ALL;

    public function mount(SepsisIndicatorService $service): void
    {
        $this->year = $service->years()[0] ?? '';
        $this->month = self::ALL;
    }

    public function updatedYear(): void
    {
        $this->month = self::ALL;
    }

    #[Computed]
    public function dashboard(): array
    {
        return app(SepsisIndicatorService::class)->dashboard(
            $this->year ?: null,
            $this->month === self::ALL ? null : $this->month,
        );
    }

    #[Computed]
    public function rows(): array
    {
        return $this->dashboard['rows'];
    }

    #[Computed]
    public function years(): array
    {
        return app(SepsisIndicatorService::class)->years();
    }

    #[Computed]
    public function months(): array
    {
        return array_column($this->rows, 'month');
    }

    #[Computed]
    public function current(): ?array
    {
        return $this->dashboard['current'];
    }

    #[Computed]
    public function distributions(): array
    {
        return $this->dashboard['distributions'];
    }

    #[Computed]
    public function cases()
    {
        return $this->dashboard['cases'];
    }

    #[Computed]
    public function costSummary(): array
    {
        return app(SepsisCostService::class)->summary(
            $this->year ?: null,
            $this->month === self::ALL ? null : $this->month,
        );
    }

    public function formatMoney(null|int|float|string $value): string
    {
        return $value === null ? '—' : '$'.number_format((float) $value, 0, ',', '.');
    }

    #[Computed]
    public function severityByCase(): array
    {
        return app(SepsisSeverityService::class)->severityByCase(
            $this->year ?: null,
            $this->month === self::ALL ? null : $this->month,
        );
    }

    #[Computed]
    public function severityMonthly(): array
    {
        return app(SepsisSeverityService::class)->monthlyTrend($this->year ?: null);
    }

    #[Computed]
    public function mortalityCrossTab(): array
    {
        return app(SepsisSeverityService::class)->mortalityCrossTab(
            $this->year ?: null,
            $this->month === self::ALL ? null : $this->month,
        );
    }

    #[Computed]
    public function costBySeverityBand(): array
    {
        return app(SepsisSeverityService::class)->costBySeverityBand(
            $this->year ?: null,
            $this->month === self::ALL ? null : $this->month,
        );
    }

    #[Computed]
    public function sourceControlTiming(): array
    {
        return app(SepsisSourceControlTimingService::class)->medianHoursByFocus(
            $this->year ?: null,
            $this->month === self::ALL ? null : $this->month,
        );
    }

    /**
     * @return array<string, array{label: string, target: float, op: string}>
     */
    public function goals(): array
    {
        return SepsisIndicatorService::GOALS;
    }

    public function meetsGoal(string $key, ?float $value): ?bool
    {
        return SepsisIndicatorService::meetsGoal($key, $value);
    }

    public function goalText(string $key): string
    {
        $goal = SepsisIndicatorService::GOALS[$key];
        $symbol = match ($goal['op']) {
            '>=' => '≥',
            '<=' => '≤',
            '<' => '<',
            default => '',
        };

        return "Meta {$symbol} ".rtrim(rtrim(number_format($goal['target'], 1, ',', '.'), '0'), ',').'%';
    }

    public function formatMinutes(?int $minutes): string
    {
        return $minutes === null ? '—' : sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function formatHours(?float $hours): string
    {
        return $hours === null ? '—' : number_format($hours, 1, ',', '.').' h';
    }

    public function formatDays(?float $days): string
    {
        return $days === null ? '—' : number_format($days, 1, ',', '.').' d';
    }

    public function formatPercentage(?float $value): string
    {
        return $value === null ? '—' : number_format($value, 1, ',', '.').'%';
    }
}
