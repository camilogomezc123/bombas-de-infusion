<?php

namespace App\Filament\Acv\Pages;

use App\Models\IndicatorTarget;
use App\Services\AcvIndicatorService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class AcvIndicators extends Page
{
    public const ALL_MONTHS = 'all';

    public const VIEWS = [
        'general' => 'Análisis general',
        'resq' => 'Indicadores RES-Q',
    ];

    public const QUARTERS = [
        'q1' => 'Q1 (enero - marzo)',
        'q2' => 'Q2 (abril - junio)',
        'q3' => 'Q3 (julio - septiembre)',
        'q4' => 'Q4 (octubre - diciembre)',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $title = 'Indicadores ACV';

    protected static ?int $navigationSort = 1;

    // Pantalla inicial del panel ACV (/acv).
    protected static ?string $slug = '/';

    protected string $view = 'filament.pages.acv-indicators';

    public string $year = '';

    public string $month = '';

    public string $dashboardView = 'general';

    public function mount(): void
    {
        $previousMonth = CarbonImmutable::now()->subMonth();

        $this->year = $previousMonth->format('Y');
        $this->month = $previousMonth->format('Y/m');
    }

    public function updatedYear(): void
    {
        $this->month = self::ALL_MONTHS;
    }

    /**
     * Períodos rápidos disponibles según los meses con datos del año.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function quickPeriods(): array
    {
        $availableMonthNumbers = array_map(
            fn (string $month): int => (int) substr($month, 5, 2),
            $this->months,
        );

        $periods = [self::ALL_MONTHS => "Año completo {$this->year}"];

        foreach (self::QUARTERS as $quarter => $label) {
            $quarterNumber = (int) substr($quarter, 1);
            $firstMonth = (($quarterNumber - 1) * 3) + 1;
            $quarterMonths = range($firstMonth, $firstMonth + 2);

            if (array_intersect($quarterMonths, $availableMonthNumbers) !== []) {
                $periods[$quarter] = $label;
            }
        }

        return $periods;
    }

    public function selectPeriod(string $period): void
    {
        $validPeriods = [
            ...array_keys($this->quickPeriods),
            ...$this->months,
        ];

        if (in_array($period, $validPeriods, true)) {
            $this->month = $period;
        }
    }

    public function selectView(string $view): void
    {
        if (array_key_exists($view, self::VIEWS)) {
            $this->dashboardView = $view;
        }
    }

    #[Computed]
    public function rows(): array
    {
        return app(AcvIndicatorService::class)->monthly($this->year ?: null);
    }

    #[Computed]
    public function years(): array
    {
        return app(AcvIndicatorService::class)->years();
    }

    /**
     * Meses disponibles para el año filtrado.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function months(): array
    {
        return array_column($this->rows, 'month');
    }

    /**
     * Fila del mes seleccionado (o el más reciente disponible).
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function current(): ?array
    {
        if ($this->month === self::ALL_MONTHS) {
            return app(AcvIndicatorService::class)->annual($this->year);
        }

        if (array_key_exists($this->month, self::QUARTERS)) {
            return app(AcvIndicatorService::class)->quarterly(
                $this->year,
                (int) substr($this->month, 1),
            );
        }

        $rows = $this->rows;

        foreach ($rows as $row) {
            if ($row['month'] === $this->month) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Metas configuradas, indexadas por clave.
     *
     * @return array<string, IndicatorTarget>
     */
    #[Computed]
    public function targets(): array
    {
        return IndicatorTarget::query()->where('is_active', true)->get()->keyBy('key')->all();
    }

    public function formatMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function formatPercentage(?float $value): string
    {
        return $value === null ? '—' : number_format($value, 1, ',', '.').'%';
    }

    /**
     * Color de semáforo para un cumplimiento porcentual, según meta configurada.
     */
    public function targetColor(?float $value, string $key, float $fallbackTarget = 80): string
    {
        if ($value === null) {
            return 'gray';
        }

        $target = $this->targets()[$key] ?? null;
        $goal = $target ? (float) $target->target_value : $fallbackTarget;
        $warning = $target ? (float) $target->warning_value : $fallbackTarget * 0.75;
        $lowerIsBetter = $target?->comparison === 'lte';

        if ($lowerIsBetter) {
            return match (true) {
                $value <= $goal => 'green',
                $value <= $warning => 'amber',
                default => 'red',
            };
        }

        return match (true) {
            $value >= $goal => 'green',
            $value >= $warning => 'amber',
            default => 'red',
        };
    }

    /**
     * Clases CSS de la insignia de semáforo.
     */
    public function badgeClasses(string $color): string
    {
        return match ($color) {
            'green' => 'bg-emerald-100 text-emerald-800',
            'amber' => 'bg-amber-100 text-amber-800',
            'red' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-500',
        };
    }

    /**
     * Compatibilidad con la tabla detallada (semáforo por cumplimiento simple).
     */
    public function complianceColor(?float $value, float $target): string
    {
        if ($value === null) {
            return 'bg-gray-100 text-gray-600';
        }

        if ($value >= $target) {
            return 'bg-emerald-100 text-emerald-800';
        }

        if ($value >= $target * 0.75) {
            return 'bg-amber-100 text-amber-800';
        }

        return 'bg-red-100 text-red-800';
    }
}
