<?php

namespace App\Filament\InfusionMonitoring\Pages;

use App\Models\IcuStay;
use App\Models\InfusionDoseSummary;
use App\Services\Infusion\VisScoreCalculator;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

/**
 * Bitácora de infusión, tendencia de VIS y dosis acumulada por fármaco para una
 * estancia específica — selección manual por caso para no cargar todas las estancias
 * a la vez. Incluye captura rápida de peso: sin un peso registrado, VIS y el screening
 * de PIS no se pueden calcular (ver VisScoreCalculator/PisRiskEvaluator).
 */
class StayInfusionTimeline extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Línea de tiempo por paciente';

    protected static ?string $title = 'Línea de tiempo de infusión';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'linea-de-tiempo';

    protected string $view = 'filament.infusion-monitoring.stay-timeline';

    public ?int $icuStayId = null;

    public array $weightData = [];

    public function mount(): void
    {
        $this->weightForm->fill(['recorded_at' => now()]);
    }

    public function weightForm(Schema $schema): Schema
    {
        return $schema->statePath('weightData')->columns(3)->components([
            TextInput::make('weight_kg')->label('Peso (kg)')->numeric()->required()->step(0.1),
            DateTimePicker::make('recorded_at')->label('Fecha y hora')->seconds(false)->required()->default(now()),
        ]);
    }

    public function saveWeight(): void
    {
        if (! $this->icuStayId) {
            return;
        }

        $data = $this->weightForm->getState();

        $stay = IcuStay::find($this->icuStayId);

        if (! $stay) {
            return;
        }

        $stay->weights()->create([
            'weight_kg' => $data['weight_kg'],
            'recorded_at' => $data['recorded_at'],
            'source' => 'manual',
            'created_by' => auth()->id(),
        ]);

        app(VisScoreCalculator::class)->snapshotFor($stay);

        $this->weightForm->fill(['recorded_at' => now()]);
        unset($this->selectedStay);

        Notification::make()->title('Peso registrado')->success()->send();
    }

    #[Computed]
    public function stays(): array
    {
        return IcuStay::query()
            ->where('status', 'active')
            ->where('is_cancelled', false)
            ->with('patient')
            ->orderBy('bed_label')
            ->get()
            ->mapWithKeys(fn (IcuStay $stay) => [$stay->id => "{$stay->case_number} · {$stay->bed_label} · ".($stay->patient?->full_name ?? 'Sin paciente')])
            ->all();
    }

    #[Computed]
    public function selectedStay(): ?IcuStay
    {
        if (! $this->icuStayId) {
            return null;
        }

        return IcuStay::query()->with([
            'patient', 'weights' => fn ($q) => $q->latest('recorded_at'),
            'infusionEvents' => fn ($q) => $q->orderByDesc('occurred_at'),
            'visScoreSnapshots' => fn ($q) => $q->orderByDesc('computed_at')->limit(20),
            'infusionClinicalAlerts' => fn ($q) => $q->orderByDesc('created_at'),
        ])->find($this->icuStayId);
    }

    #[Computed]
    public function doseSummaries(): array
    {
        $stay = $this->selectedStay;

        if (! $stay) {
            return [];
        }

        return $stay->infusionEvents->isEmpty() ? [] : InfusionDoseSummary::query()
            ->where('icu_stay_id', $stay->id)
            ->with('drugInfusionProfile')
            ->orderByDesc('summary_date')
            ->get()
            ->groupBy(fn ($row) => $row->drugInfusionProfile?->name ?? '—')
            ->all();
    }
}
