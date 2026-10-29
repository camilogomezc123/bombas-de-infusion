<?php

namespace App\Services;

use App\Models\SepsisCase;
use Illuminate\Support\Collection;

class SepsisIndicatorService
{
    /**
     * Los 6 indicadores principales con su meta institucional.
     * op: comparación del valor contra la meta para considerarla cumplida.
     */
    public const GOALS = [
        'bundle_pct' => ['label' => 'Adherencia al bundle (AB + lactato + hemocultivos ≤ 60 min)', 'target' => 70.0, 'op' => '>='],
        'map_goal_pct' => ['label' => 'Meta de PAM en las primeras 3 h (choque séptico)', 'target' => 70.0, 'op' => '>='],
        'mort_hosp_sepsis_pct' => ['label' => 'Mortalidad hospitalaria por sepsis', 'target' => 17.5, 'op' => '<'],
        'mort_hosp_shock_pct' => ['label' => 'Mortalidad hospitalaria por choque séptico', 'target' => 60.0, 'op' => '<='],
        'mort_30d_sepsis_pct' => ['label' => 'Mortalidad a 30 días por sepsis', 'target' => 30.0, 'op' => '<='],
        'mort_30d_shock_pct' => ['label' => 'Mortalidad a 30 días por choque séptico', 'target' => 60.0, 'op' => '<='],
    ];

    /**
     * ¿El valor cumple la meta del indicador? Null cuando no hay dato.
     */
    public static function meetsGoal(string $key, ?float $value): ?bool
    {
        if ($value === null || ! isset(self::GOALS[$key])) {
            return null;
        }

        $goal = self::GOALS[$key];

        return match ($goal['op']) {
            '>=' => $value >= $goal['target'],
            '<=' => $value <= $goal['target'],
            '<' => $value < $goal['target'],
            default => null,
        };
    }

    /**
     * Datos completos del tablero con una sola lectura de casos.
     *
     * @return array{rows: array<int, array<string, mixed>>, current: array<string, mixed>|null, distributions: array<string, mixed>, cases: Collection}
     */
    public function dashboard(?string $year = null, ?string $month = null): array
    {
        $cases = $this->baseQuery($year)
            ->with('patient')
            ->orderBy('month')
            ->orderBy('activation_at')
            ->get();
        $selectedCases = $month
            ? $cases->where('month', $month)->values()
            : $cases;

        return [
            'rows' => $cases
                ->groupBy('month')
                ->map(fn (Collection $monthCases, string $caseMonth): array => $this->calculateMonth($caseMonth, $monthCases))
                ->values()
                ->all(),
            'current' => $selectedCases->isEmpty()
                ? null
                : $this->calculateMonth($month ?? ($year ?: 'Total'), $selectedCases),
            'distributions' => $this->calculateDistributions($selectedCases),
            'cases' => $selectedCases,
        ];
    }

    /**
     * Casos válidos, con mes y no anulados (base de los indicadores).
     */
    private function baseQuery(?string $year = null)
    {
        return SepsisCase::query()
            ->where('is_valid', true)
            ->where('is_cancelled', false)
            ->whereNotNull('month')
            ->where('month', '!=', '')
            ->when($year, fn ($q) => $q->where('month', 'like', "{$year}/%"));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function monthly(?string $year = null): array
    {
        return $this->baseQuery($year)
            ->orderBy('month')
            ->get()
            ->groupBy('month')
            ->map(fn (Collection $cases, string $month): array => $this->calculateMonth($month, $cases))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function years(): array
    {
        return $this->baseQuery()
            ->select('month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn (string $m): string => substr($m, 0, 4))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resumen agregado del período (un mes específico o todo el año).
     *
     * @return array<string, mixed>|null
     */
    public function summary(?string $year = null, ?string $month = null): ?array
    {
        $cases = $this->baseQuery($year)
            ->when($month, fn ($q) => $q->where('month', $month))
            ->get();

        if ($cases->isEmpty()) {
            return null;
        }

        return $this->calculateMonth($month ?? ($year ?: 'Total'), $cases);
    }

    /**
     * Distribuciones (tortas) del período seleccionado.
     *
     * @return array<string, array<int, array{label: string, value: int}>>
     */
    public function distributions(?string $year = null, ?string $month = null): array
    {
        $cases = $this->baseQuery($year)
            ->when($month, fn ($q) => $q->where('month', $month))
            ->get();

        return $this->calculateDistributions($cases);
    }

    /**
     * @return array<string, array<int, array{label: string, value: int}>>
     */
    private function calculateDistributions(Collection $cases): array
    {
        return [
            'culture_before' => $this->yesNo($cases, 'culture_before_ab'),
            'ab_adjusted' => $this->yesNo($cases, 'ab_adjusted'),
            'focus' => $cases
                ->groupBy(fn (SepsisCase $c): string => $c->infection_focus ?: 'Sin foco identificado')
                ->map(fn (Collection $g, string $label): array => ['label' => $label, 'value' => $g->count()])
                ->sortByDesc('value')
                ->values()
                ->all(),
        ];
    }

    private function calculateMonth(string $month, Collection $cases): array
    {
        $diagAb = $this->durations($cases, 'activation_at', 'antibiotic_at');
        $diagDrain = $this->durations($cases, 'activation_at', 'drainage_at');

        // Bundle de la primera hora: denominador = casos con hora de activación.
        $activated = $cases->filter(fn (SepsisCase $c): bool => filled($c->activation_at));
        $ab60 = $activated->filter(fn (SepsisCase $c): bool => $this->within($c, 'antibiotic_at', 60));
        $lactate60 = $activated->filter(fn (SepsisCase $c): bool => $this->within($c, 'lactate_at', 60));
        $culture60 = $activated->filter(fn (SepsisCase $c): bool => $this->within($c, 'culture_at', 60));
        $bundle = $activated->filter(fn (SepsisCase $c): bool => $this->within($c, 'antibiotic_at', 60)
            && $this->within($c, 'lactate_at', 60)
            && $this->within($c, 'culture_at', 60));

        // Poblaciones sepsis (sin choque) y choque séptico.
        $shock = $cases->filter(fn (SepsisCase $c): bool => (bool) $c->septic_shock);
        $sepsisOnly = $cases->filter(fn (SepsisCase $c): bool => ! $c->septic_shock);
        $mapMet = $shock->filter(fn (SepsisCase $c): bool => (bool) $c->map_goal_met);
        $died30dShock = $shock->filter(fn (SepsisCase $c): bool => $this->died30d($c));
        $died30dSepsis = $sepsisOnly->filter(fn (SepsisCase $c): bool => $this->died30d($c));

        return [
            'month' => $month,
            'total' => $cases->count(),
            'activated_total' => $activated->count(),
            'sepsis_total' => $sepsisOnly->count(),
            'shock_total' => $shock->count(),
            'bundle_ab_pct' => $this->percentage($ab60->count(), $activated->count()),
            'bundle_lactate_pct' => $this->percentage($lactate60->count(), $activated->count()),
            'bundle_culture_pct' => $this->percentage($culture60->count(), $activated->count()),
            'bundle_pct' => $this->percentage($bundle->count(), $activated->count()),
            'map_goal_pct' => $this->percentage($mapMet->count(), $shock->count()),
            'mort_hosp_sepsis_pct' => $this->percentage($sepsisOnly->where('deceased', true)->count(), $sepsisOnly->count()),
            'mort_hosp_shock_pct' => $this->percentage($shock->where('deceased', true)->count(), $shock->count()),
            'mort_30d_sepsis_pct' => $this->percentage($died30dSepsis->count(), $sepsisOnly->count()),
            'mort_30d_shock_pct' => $this->percentage($died30dShock->count(), $shock->count()),
            'diag_ab' => $this->median($diagAb),
            'diag_drainage' => $this->median($diagDrain),
            'er_stay_hours' => $this->medianFloat($cases->pluck('er_stay_days')->map(fn ($d) => $d === null ? null : (float) $d * 24)),
            'clinic_stay_days' => $this->medianFloat($cases->pluck('clinic_stay_days')->map(fn ($d) => $d === null ? null : (float) $d)),
            'uci_stay_days' => $this->medianFloat($cases->where('uci', true)->pluck('uci_stay_days')->map(fn ($d) => $d === null ? null : (float) $d)),
            'culture_before_pct' => $this->percentage($cases->where('culture_before_ab', true)->count(), $cases->count()),
            'ab_adjusted_pct' => $this->percentage(
                $cases->where('ab_adjusted', true)->count(),
                $cases->whereNotNull('ab_adjusted')->count(),
            ),
            'deaths' => $cases->where('deceased', true)->count(),
            'mortality_pct' => $this->percentage($cases->where('deceased', true)->count(), $cases->count()),
        ];
    }

    /**
     * ¿El hito ocurrió dentro de los N minutos posteriores a la activación del código?
     */
    private function within(SepsisCase $case, string $field, int $minutes): bool
    {
        if (! filled($case->activation_at) || ! filled($case->{$field})) {
            return false;
        }

        $diff = $case->activation_at->diffInMinutes($case->{$field}, false);

        return $diff >= 0 && $diff <= $minutes;
    }

    /**
     * Muerte dentro de los 30 días posteriores a la activación (o al ingreso).
     * Si el fallecimiento no tiene fecha registrada, se asume hospitalario (cuenta).
     */
    private function died30d(SepsisCase $case): bool
    {
        $reference = $case->activation_at ?? $case->admission_at ?? $case->registered_on;

        if ($case->death_at && $reference) {
            $days = $reference->diffInDays($case->death_at, false);

            return $days >= 0 && $days <= 30;
        }

        return (bool) $case->deceased;
    }

    /**
     * @return array<int, int>  minutos entre dos fechas
     */
    private function durations(Collection $cases, string $start, string $end, int $maxMinutes = 20160): array
    {
        return $cases
            ->filter(fn (SepsisCase $c): bool => filled($c->{$start}) && filled($c->{$end}))
            ->map(fn (SepsisCase $c): int => (int) $c->{$start}->diffInMinutes($c->{$end}))
            ->filter(fn (int $m): bool => $m >= 0 && $m <= $maxMinutes)
            ->sort()
            ->values()
            ->all();
    }

    private function median(array $values): ?int
    {
        $count = count($values);

        if ($count === 0) {
            return null;
        }

        $mid = intdiv($count, 2);

        return $count % 2
            ? (int) $values[$mid]
            : (int) round(($values[$mid - 1] + $values[$mid]) / 2);
    }

    private function medianFloat(Collection $values): ?float
    {
        $values = $values->filter(fn ($v) => $v !== null)->sort()->values();
        $count = $values->count();

        if ($count === 0) {
            return null;
        }

        $mid = intdiv($count, 2);

        return $count % 2
            ? round((float) $values[$mid], 2)
            : round(((float) $values[$mid - 1] + (float) $values[$mid]) / 2, 2);
    }

    private function percentage(int $numerator, int $denominator): ?float
    {
        return $denominator === 0 ? null : round(($numerator / $denominator) * 100, 1);
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function yesNo(Collection $cases, string $field): array
    {
        return [
            ['label' => 'Sí', 'value' => $cases->where($field, true)->count()],
            ['label' => 'No', 'value' => $cases->where($field, false)->count()],
        ];
    }
}
