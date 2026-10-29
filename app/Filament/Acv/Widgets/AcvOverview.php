<?php

namespace App\Filament\Acv\Widgets;

use App\Enums\CaseStatus;
use App\Models\AcvCase;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AcvOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $activeCases = AcvCase::query()->where('is_cancelled', false);
        $total = (clone $activeCases)->count();
        $hospitalized = (clone $activeCases)->where('status', CaseStatus::Hospitalized)->count();
        $recurrences = (clone $activeCases)->where('is_recurrence', true)->count();

        $doorToImage = (clone $activeCases)
            ->whereNotNull('arrival_at')
            ->whereNotNull('imaging_at')
            ->get(['arrival_at', 'imaging_at'])
            ->map(fn (AcvCase $case): int => (int) $case->arrival_at->diffInMinutes($case->imaging_at))
            ->filter(fn (int $minutes): bool => $minutes >= 0 && $minutes <= 1440)
            ->sort()
            ->values();

        $medianDoorToImage = $this->median($doorToImage->all());

        return [
            Stat::make('Casos ACV', number_format($total, 0, ',', '.'))
                ->description('Registros activos en el programa')
                ->descriptionIcon('heroicon-m-heart')
                ->color('primary'),
            Stat::make('Hospitalizados', number_format($hospitalized, 0, ',', '.'))
                ->description('Pendientes de egreso para auditoría')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color($hospitalized > 0 ? 'warning' : 'success'),
            Stat::make('Recurrencias', number_format($recurrences, 0, ',', '.'))
                ->description('Pacientes con más de un caso')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
            Stat::make('Mediana puerta-imagen', $medianDoorToImage === null ? 'Sin datos' : "{$medianDoorToImage} min")
                ->description('Todos los casos con tiempos válidos')
                ->descriptionIcon('heroicon-m-clock')
                ->color(match (true) {
                    $medianDoorToImage === null => 'gray',
                    $medianDoorToImage <= 30 => 'success',
                    $medianDoorToImage <= 60 => 'warning',
                    default => 'danger',
                }),
        ];
    }

    private function median(array $values): ?int
    {
        $count = count($values);

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);

        return $count % 2
            ? (int) $values[$middle]
            : (int) round(($values[$middle - 1] + $values[$middle]) / 2);
    }
}
