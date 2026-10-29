<?php

namespace App\Filament\Acv\Widgets;

use App\Models\AcvCase;
use Filament\Widgets\ChartWidget;

class CasesByMonthChart extends ChartWidget
{
    protected ?string $heading = 'Casos ACV por mes';

    protected ?string $description = 'Tendencia de ingresos registrados, excluyendo casos anulados.';

    protected function getData(): array
    {
        $cases = AcvCase::query()
            ->where('is_cancelled', false)
            ->whereNotNull('month')
            ->selectRaw('month, count(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->take(-12);

        return [
            'datasets' => [[
                'label' => 'Casos',
                'data' => $cases->pluck('total')->all(),
                'borderColor' => '#17375E',
                'backgroundColor' => 'rgba(184, 148, 69, 0.22)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $cases->pluck('month')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
