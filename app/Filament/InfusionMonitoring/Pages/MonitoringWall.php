<?php

namespace App\Filament\InfusionMonitoring\Pages;

use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;
use App\Models\InfusionDoseSummary;
use App\Models\InfusionEvent;
use App\Models\VisScoreSnapshot;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Central de monitoreo: una grilla de cubículos pensada para una pantalla compartida
 * (sala de enfermería/TV), al estilo de un monitor central de signos vitales — no un
 * flujo de trabajo clínico. Se actualiza sola (wire:poll) y no sustituye el monitoreo
 * directo del paciente ni la vigilancia de enfermería.
 */
class MonitoringWall extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTv;

    protected static ?string $navigationLabel = 'Central de Monitoreo';

    protected static ?string $title = 'Central de Monitoreo';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'central-monitoreo';

    protected string $view = 'filament.infusion-monitoring.monitoring-wall';

    public bool $onlyAlerts = false;

    public bool $sortByUrgency = false;

    /**
     * Hasta 2 ids de IcuStay seleccionados para comparar lado a lado (tendencia VIS
     * superpuesta) — útil en rounds para discutir evolución relativa entre pacientes.
     *
     * @var array<int, int>
     */
    public array $compareIds = [];

    /**
     * Espejo público de summaryCounts['critical'] para que Alpine pueda reaccionar a su
     * cambio vía @entangle (un método #[Computed] no se puede entangle directamente).
     */
    public int $criticalAlertCount = 0;

    public function acknowledgeAlert(int $alertId): void
    {
        $alert = InfusionClinicalAlert::find($alertId);

        if (! $alert || $alert->status !== 'open') {
            return;
        }

        $alert->update(['status' => 'acknowledged', 'acknowledged_by' => auth()->id(), 'acknowledged_at' => now()]);

        unset($this->tiles, $this->summaryCounts);

        Notification::make()->title('Alerta reconocida')->success()->send();
    }

    public function toggleCompare(int $stayId): void
    {
        if (in_array($stayId, $this->compareIds, true)) {
            $this->compareIds = array_values(array_diff($this->compareIds, [$stayId]));

            return;
        }

        if (count($this->compareIds) >= 2) {
            array_shift($this->compareIds);
        }

        $this->compareIds[] = $stayId;
    }

    #[Computed]
    public function summaryCounts(): array
    {
        $tiles = collect($this->tiles());

        $counts = [
            'critical' => $tiles->where('severity', 'critical')->count(),
            'warning' => $tiles->where('severity', 'warning')->count(),
            'info' => $tiles->where('severity', 'info')->count(),
            'good' => $tiles->where('severity', 'good')->count(),
        ];

        $this->criticalAlertCount = $counts['critical'];

        return $counts;
    }

    #[Computed]
    public function compareTiles(): array
    {
        if (count($this->compareIds) !== 2) {
            return [];
        }

        $byId = collect($this->tiles())->keyBy(fn (array $tile) => $tile['stay']->id);

        return collect($this->compareIds)
            ->map(fn (int $id) => $byId->get($id))
            ->filter()
            ->values()
            ->all();
    }

    #[Computed]
    public function tiles(): array
    {
        $severityRank = ['critical' => 0, 'warning' => 1, 'info' => 2, 'good' => 3];

        $tiles = $this->buildTiles();

        if ($this->onlyAlerts) {
            $tiles = $tiles->filter(fn (array $tile) => $tile['open_alerts']->isNotEmpty())->values();
        }

        if ($this->sortByUrgency) {
            $tiles = $tiles->sortBy(fn (array $tile) => $severityRank[$tile['severity']] ?? 4)->values();
        }

        return $tiles->all();
    }

    private function buildTiles(): Collection
    {
        return IcuStay::query()
            ->where('status', 'active')
            ->where('is_cancelled', false)
            ->with([
                'patient', 'weights' => fn ($q) => $q->latest('recorded_at')->limit(1),
                'infusionEvents' => fn ($q) => $q->latest('occurred_at')->limit(20),
                'infusionEvents.drugInfusionProfile',
                'visScoreSnapshots' => fn ($q) => $q->orderByDesc('computed_at')->limit(12),
                'infusionClinicalAlerts' => fn ($q) => $q->where('status', '!=', 'resolved'),
            ])
            ->orderBy('bed_label')
            ->get()
            ->filter(fn (IcuStay $stay) => $stay->infusionEvents->isNotEmpty())
            ->map(function (IcuStay $stay): array {
                $openAlerts = $stay->infusionClinicalAlerts;
                $severity = match (true) {
                    $openAlerts->contains(fn ($a) => $a->severity === 'critical') => 'critical',
                    $openAlerts->contains(fn ($a) => $a->severity === 'warning') => 'warning',
                    $openAlerts->isNotEmpty() => 'info',
                    default => 'good',
                };

                // Un dispositivo multicanal puede infundir más de un fármaco a la vez
                // (ej. EVO LVP de 2 canales): se muestra el último evento por canal, no
                // solo "el último evento de la estancia" — así se ve en una central real.
                $channels = $stay->infusionEvents
                    ->groupBy(fn (InfusionEvent $event) => $event->channel_number ?? 1)
                    ->map(function ($events, $channelNumber) use ($openAlerts): array {
                        $event = $events->first();

                        $doseSummary = $event->drug_infusion_profile_id
                            ? InfusionDoseSummary::query()
                                ->where('icu_stay_id', $event->icu_stay_id)
                                ->where('drug_infusion_profile_id', $event->drug_infusion_profile_id)
                                ->orderByDesc('summary_date')
                                ->first()
                            : null;

                        // Alertas propias de este fármaco (dosis máxima, PIS, interrupción de
                        // antibiótico...), no el conteo agregado de toda la estancia — así se
                        // ve junto al canal que realmente la generó.
                        $drugAlerts = $event->drug_infusion_profile_id
                            ? $openAlerts->where('drug_infusion_profile_id', $event->drug_infusion_profile_id)->values()
                            : collect();

                        // Tendencia de la tasa real infundida (no derivada como el VIS) —
                        // lo que una central tipo Fresenius muestra como gráfico de tasa
                        // por canal, además del número puntual.
                        $rateSeries = $event->drug_infusion_profile_id
                            ? InfusionEvent::query()
                                ->where('icu_stay_id', $event->icu_stay_id)
                                ->where('drug_infusion_profile_id', $event->drug_infusion_profile_id)
                                ->latest('occurred_at')
                                ->limit(10)
                                ->get()
                                ->reverse()
                                ->values()
                                ->map(fn (InfusionEvent $e) => (float) ($e->existing_dose_rate_value ?? $e->attempted_value ?? 0))
                                ->all()
                            : [];

                        return [
                            'channel_number' => (int) $channelNumber,
                            'event' => $event,
                            'dose_summary' => $doseSummary,
                            'profile_category' => $event->drugInfusionProfile?->category,
                            'drug_alerts' => $drugAlerts,
                            'rate_series' => $rateSeries,
                        ];
                    })
                    ->sortKeys()
                    ->values();

                $lastEvent = $stay->infusionEvents->first();
                $visSeries = $stay->visScoreSnapshots->reverse()->values();
                $visCurrent = $visSeries->last();
                $visPrevious = $visSeries->count() > 1 ? $visSeries->get($visSeries->count() - 2) : null;
                $visDelta = ($visCurrent && $visPrevious)
                    ? round((float) $visCurrent->vis_score - (float) $visPrevious->vis_score, 2)
                    : null;

                // Valor basal (primer cálculo de la estancia, no necesariamente dentro de
                // los últimos 12 snapshots cargados arriba) — da contexto de si el paciente
                // mejora o empeora desde que llegó, no solo la tendencia de las últimas horas.
                $visBaseline = VisScoreSnapshot::query()
                    ->where('icu_stay_id', $stay->id)
                    ->oldest('computed_at')
                    ->first();
                $visVsBaseline = ($visCurrent && $visBaseline && $visBaseline->id !== $visCurrent->id)
                    ? round((float) $visCurrent->vis_score - (float) $visBaseline->vis_score, 2)
                    : null;

                return [
                    'stay' => $stay,
                    'has_weight' => $stay->weights->isNotEmpty(),
                    'device_status' => $lastEvent?->device_status,
                    'battery_pct' => $lastEvent?->battery_pct,
                    'channels' => $channels,
                    'vis_current' => $visCurrent,
                    'vis_delta' => $visDelta,
                    'vis_baseline' => $visBaseline,
                    'vis_vs_baseline' => $visVsBaseline,
                    'vis_series' => $visSeries->pluck('vis_score')->map(fn ($v) => (float) $v)->all(),
                    'open_alerts' => $openAlerts,
                    'severity' => $severity,
                ];
            })
            ->values();
    }
}
