<?php

namespace App\Filament\InfusionMonitoring\Pages;

use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;
use App\Models\VisScoreSnapshot;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

/**
 * Resumen automático por cama de lo ocurrido en un turno (día 07-19h o noche 19-07h)
 * para el relevo de enfermería/médicos — qué cambió, qué alertas se abrieron/resolvieron
 * y cómo evolucionó el VIS, sin tener que reconstruirlo manualmente revisando la
 * bitácora evento por evento. Pensado para imprimirse o leerse al cierre de turno.
 */
class ShiftHandoffReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Reporte de turno';

    protected static ?string $title = 'Reporte de cambio de turno';

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'reporte-turno';

    protected string $view = 'filament.infusion-monitoring.shift-handoff';

    public string $date;

    public string $shift = 'day';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function window(): array
    {
        $base = CarbonImmutable::parse($this->date)->startOfDay();

        if ($this->shift === 'day') {
            return [$base->setTime(7, 0), $base->setTime(19, 0)];
        }

        return [$base->setTime(19, 0), $base->addDay()->setTime(7, 0)];
    }

    #[Computed]
    public function rows(): array
    {
        [$start, $end] = $this->window();

        return IcuStay::query()
            ->where('status', 'active')
            ->where('is_cancelled', false)
            ->with(['patient'])
            ->orderBy('bed_label')
            ->get()
            ->map(function (IcuStay $stay) use ($start, $end): array {
                $events = $stay->infusionEvents()
                    ->whereBetween('occurred_at', [$start, $end])
                    ->orderBy('occurred_at')
                    ->get();

                $alertsOpened = $stay->infusionClinicalAlerts()
                    ->whereBetween('created_at', [$start, $end])
                    ->get();

                $alertsResolved = InfusionClinicalAlert::query()
                    ->where('icu_stay_id', $stay->id)
                    ->whereBetween('resolved_at', [$start, $end])
                    ->get();

                $visStart = VisScoreSnapshot::query()
                    ->where('icu_stay_id', $stay->id)
                    ->where('computed_at', '>=', $start)
                    ->oldest('computed_at')
                    ->first();

                $visEnd = VisScoreSnapshot::query()
                    ->where('icu_stay_id', $stay->id)
                    ->where('computed_at', '<=', $end)
                    ->latest('computed_at')
                    ->first();

                return [
                    'stay' => $stay,
                    'drugs' => $events->pluck('medication_name_raw')->unique()->values(),
                    'events_count' => $events->count(),
                    'hard_overrides' => $events->filter(fn ($e) => $e->isHardLimitOverride())->count(),
                    'alerts_opened' => $alertsOpened,
                    'alerts_resolved' => $alertsResolved,
                    'vis_start' => $visStart?->vis_score,
                    'vis_end' => $visEnd?->vis_score,
                ];
            })
            ->filter(fn (array $row) => $row['events_count'] > 0 || $row['alerts_opened']->isNotEmpty())
            ->values()
            ->all();
    }
}
