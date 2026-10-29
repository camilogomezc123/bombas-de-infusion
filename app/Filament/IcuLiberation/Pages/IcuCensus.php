<?php

namespace App\Filament\IcuLiberation\Pages;

use App\Models\IcuStay;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

/**
 * Censo en tiempo real de estancias activas. El semáforo es una aproximación
 * (verde/amarillo/rojo/gris según registros del día) — la escala completa de 6 colores
 * del programa (incluye azul "programado" y morado "requiere decisión interdisciplinaria")
 * queda documentada como siguiente fase, ver MAPA_FUNCIONAL_ICU_LIBERATION.md.
 */
class IcuCensus extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Censo UCI';

    protected static ?string $title = 'Censo UCI';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'censo';

    protected string $view = 'filament.icu-liberation.census';

    #[Computed]
    public function stays(): array
    {
        return IcuStay::query()
            ->where('status', 'active')
            ->where('is_cancelled', false)
            ->with([
                'patient', 'icuUnit',
                'painAssessments' => fn ($q) => $q->latest('assessed_at')->limit(1),
                'sedationAssessments' => fn ($q) => $q->latest('assessed_at')->limit(1),
                'deliriumAssessments' => fn ($q) => $q->latest('assessed_at')->limit(1),
                'mobilitySessions' => fn ($q) => $q->latest('performed_at')->limit(1),
                'familyEngagements' => fn ($q) => $q->latest('occurred_at')->limit(1),
                'physicalRestraints' => fn ($q) => $q->whereNull('removed_at'),
                'satTrials', 'sbtTrials',
            ])
            ->orderBy('admission_at')
            ->get()
            ->map(fn (IcuStay $stay): array => [
                'stay' => $stay,
                'icu_day' => $stay->admission_at ? $stay->admission_at->diffInDays(now()) + 1 : null,
                'ventilation_day' => $stay->mechanical_ventilation && $stay->ventilation_start_at
                    ? $stay->ventilation_start_at->diffInDays(now()) + 1 : null,
                'pain' => $stay->painAssessments->first(),
                'sedation' => $stay->sedationAssessments->first(),
                'delirium' => $stay->deliriumAssessments->first(),
                'mobility' => $stay->mobilitySessions->first(),
                'family_present' => $stay->familyEngagements->isNotEmpty(),
                'restraints' => $stay->physicalRestraints->count(),
                'bundle_status' => $this->bundleStatusToday($stay),
            ])
            ->all();
    }

    /**
     * @return array{color: string, label: string, done: int, applicable: int}
     */
    private function bundleStatusToday(IcuStay $stay): array
    {
        $summary = $stay->bundleSummaryForDate(today());

        $label = match ($summary['color']) {
            'green' => 'Bundle del día cumplido',
            'yellow' => "Parcial ({$summary['done_count']}/{$summary['applicable_count']})",
            'gray' => 'Sin componentes aplicables',
            default => 'Pendiente hoy',
        };

        return [
            'color' => $summary['color'],
            'label' => $label,
            'done' => $summary['done_count'],
            'applicable' => $summary['applicable_count'],
        ];
    }
}
