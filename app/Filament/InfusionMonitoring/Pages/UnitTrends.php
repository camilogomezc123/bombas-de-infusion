<?php

namespace App\Filament\InfusionMonitoring\Pages;

use App\Models\InfusionClinicalAlert;
use App\Models\VisScoreSnapshot;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

/**
 * Comparativo semanal por unidad (score VIS promedio y alertas clínicas abiertas) — un
 * benchmarking entre unidades/turnos en vez de solo ver la tendencia de un paciente a la
 * vez, como ofrecen los reportes agregados de las centrales de bombas comerciales.
 * No se mezcla la dosis acumulada entre unidades porque cada fármaco tiene su propia
 * unidad de dosis (mg vs. unidades): mezclarlas produciría un total sin sentido clínico.
 */
class UnitTrends extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Tendencia por unidad';

    protected static ?string $title = 'Comparativo semanal por unidad';

    protected static ?int $navigationSort = 87;

    protected static ?string $slug = 'tendencia-unidad';

    protected string $view = 'filament.infusion-monitoring.unit-trends';

    #[Computed]
    public function visTrend(): array
    {
        return $this->weeklyHeatmap(
            VisScoreSnapshot::query()->with('icuStay.icuUnit')->get(),
            fn (VisScoreSnapshot $snapshot) => $snapshot->icuStay?->icuUnit?->name,
            fn (VisScoreSnapshot $snapshot) => $snapshot->computed_at,
            fn ($group) => round((float) $group->avg('vis_score'), 2),
        );
    }

    #[Computed]
    public function alertTrend(): array
    {
        return $this->weeklyHeatmap(
            InfusionClinicalAlert::query()->with('icuStay.icuUnit')->get(),
            fn (InfusionClinicalAlert $alert) => $alert->icuStay?->icuUnit?->name,
            fn (InfusionClinicalAlert $alert) => $alert->created_at,
            fn ($group) => $group->count(),
        );
    }

    /**
     * @return array{weeks: array<int, string>, units: array<int, array{unit: string, values: array<string, float|int|null>}>}
     */
    private function weeklyHeatmap(\Illuminate\Support\Collection $records, \Closure $unitResolver, \Closure $dateResolver, \Closure $aggregator): array
    {
        $byUnit = $records
            ->filter(fn ($record) => $unitResolver($record) !== null)
            ->groupBy($unitResolver);

        $weeks = $records
            ->map(fn ($record) => $dateResolver($record)?->startOfWeek()->format('Y-m-d'))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $units = $byUnit->map(function ($group, $unit) use ($weeks, $dateResolver, $aggregator): array {
            $byWeek = $group->groupBy(fn ($record) => $dateResolver($record)?->startOfWeek()->format('Y-m-d'));

            return [
                'unit' => $unit,
                'values' => collect($weeks)->mapWithKeys(fn ($week) => [$week => $byWeek->has($week) ? $aggregator($byWeek->get($week)) : null])->all(),
            ];
        })->values()->all();

        return ['weeks' => $weeks, 'units' => $units];
    }
}
