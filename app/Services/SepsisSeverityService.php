<?php

namespace App\Services;

use App\Models\SepsisCase;
use Illuminate\Support\Collection;

/**
 * Análisis de severidad (NEWS2 / SOFA) por paciente, histórico mensual, y mortalidad
 * cruzada por severidad y costo. Servicio independiente de SepsisIndicatorService:
 * no calcula, no recalcula ni sustituye ninguno de los 6 indicadores institucionales.
 *
 * Usa el NEWS2/SOFA MÁXIMO registrado por caso (peor valor observado durante el
 * episodio, tomado de sepsis_screenings), por ser el más relevante para correlacionar
 * con mortalidad. Las bandas de severidad y de costo son aproximaciones de referencia,
 * no una definición clínica institucional — pueden ajustarse si el programa define otras.
 */
class SepsisSeverityService
{
    private const NEWS2_BANDS = ['low' => 'Bajo (0-4)', 'medium' => 'Medio (5-6)', 'high' => 'Alto (≥7)'];

    private const SOFA_BANDS = ['low' => 'Bajo (0-6)', 'medium' => 'Medio (7-9)', 'high' => 'Alto (≥10)'];

    private const COST_BANDS = ['low' => 'Costo bajo', 'medium' => 'Costo medio', 'high' => 'Costo alto'];

    /**
     * Severidad máxima (NEWS2/SOFA) por caso del período — base de "por paciente" e "histórico".
     */
    public function casesSeverity(?string $year, ?string $month): Collection
    {
        $query = SepsisCase::query()
            ->where('is_valid', true)
            ->where('is_cancelled', false)
            ->whereNotNull('month')
            ->where('month', '!=', '')
            ->with('patient')
            ->withMax('screenings as max_news2', 'news2_score')
            ->withMax('screenings as max_sofa', 'sofa_score');

        if ($year) {
            $query->where('month', 'like', "{$year}/%");
        }

        if ($month) {
            $query->where('month', $month);
        }

        return $query->get();
    }

    /**
     * Mapa case_id => ['news2' => ?int, 'sofa' => ?int] para mostrar en el detalle por paciente.
     *
     * @return array<int, array{news2: ?int, sofa: ?int}>
     */
    public function severityByCase(?string $year, ?string $month): array
    {
        return $this->casesSeverity($year, $month)
            ->mapWithKeys(fn (SepsisCase $case): array => [
                $case->id => ['news2' => $case->max_news2, 'sofa' => $case->max_sofa],
            ])
            ->all();
    }

    /**
     * Promedio mensual del NEWS2 y SOFA máximo por caso — evolución histórica.
     *
     * @return array<int, array{month: string, avg_news2: ?float, avg_sofa: ?float, n: int}>
     */
    public function monthlyTrend(?string $year): array
    {
        return $this->casesSeverity($year, null)
            ->groupBy('month')
            ->map(function (Collection $cases, string $month): array {
                $news2 = $cases->pluck('max_news2')->filter(fn ($v) => $v !== null);
                $sofa = $cases->pluck('max_sofa')->filter(fn ($v) => $v !== null);

                return [
                    'month' => $month,
                    'avg_news2' => $news2->isNotEmpty() ? round($news2->avg(), 1) : null,
                    'avg_sofa' => $sofa->isNotEmpty() ? round($sofa->avg(), 1) : null,
                    'n' => $cases->count(),
                ];
            })
            ->sortBy('month')
            ->values()
            ->all();
    }

    /**
     * Mortalidad cruzada por banda de severidad (NEWS2 y SOFA) y banda de costo (terciles del período).
     *
     * @return array{news2: array, sofa: array, cost_bands: array}
     */
    public function mortalityCrossTab(?string $year, ?string $month): array
    {
        $cases = $this->casesSeverity($year, $month)->filter(fn (SepsisCase $case): bool => $case->total_cost !== null);
        $breakpoints = $this->tercileBreakpoints($cases->pluck('total_cost')->map(fn ($v): float => (float) $v)->sort()->values());

        return [
            'news2' => $this->buildCrossTab($cases, 'max_news2', fn (?int $v) => $this->band($v, self::NEWS2_BANDS, [5, 7]), $breakpoints),
            'sofa' => $this->buildCrossTab($cases, 'max_sofa', fn (?int $v) => $this->band($v, self::SOFA_BANDS, [7, 10]), $breakpoints),
            'cost_bands' => self::COST_BANDS,
            'cost_breakpoints' => $breakpoints,
            'count' => $cases->count(),
        ];
    }

    /**
     * Costo promedio, mínimo, máximo y n por banda de NEWS2 y de SOFA (independiente de
     * la mortalidad cruzada) — responde directamente "costo por NEWS2/SOFA".
     *
     * @return array{news2: array, sofa: array}
     */
    public function costBySeverityBand(?string $year, ?string $month): array
    {
        $cases = $this->casesSeverity($year, $month)->filter(fn (SepsisCase $case): bool => $case->total_cost !== null);

        return [
            'news2' => $this->costByBand($cases, 'max_news2', self::NEWS2_BANDS, [5, 7]),
            'sofa' => $this->costByBand($cases, 'max_sofa', self::SOFA_BANDS, [7, 10]),
        ];
    }

    /**
     * @param  array<string, string>  $bands
     * @param  array{0: int, 1: int}  $thresholds
     * @return array<int, array{label: string, n: int, avg_cost: ?float, min_cost: ?float, max_cost: ?float}>
     */
    private function costByBand(Collection $cases, string $scoreField, array $bands, array $thresholds): array
    {
        $rows = [];

        foreach ($bands as $bandKey => $label) {
            $group = $cases->filter(fn (SepsisCase $case): bool => $this->band($case->{$scoreField}, $bands, $thresholds) === $bandKey);
            $costs = $group->pluck('total_cost')->map(fn ($v): float => (float) $v);

            $rows[] = [
                'label' => $label,
                'n' => $group->count(),
                'avg_cost' => $costs->isNotEmpty() ? $costs->avg() : null,
                'min_cost' => $costs->isNotEmpty() ? $costs->min() : null,
                'max_cost' => $costs->isNotEmpty() ? $costs->max() : null,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $bands
     * @param  array{0: int, 1: int}  $thresholds  [medio desde, alto desde]
     */
    private function band(?int $value, array $bands, array $thresholds): ?string
    {
        if ($value === null) {
            return null;
        }

        return match (true) {
            $value >= $thresholds[1] => 'high',
            $value >= $thresholds[0] => 'medium',
            default => 'low',
        };
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function tercileBreakpoints(Collection $sortedCosts): array
    {
        if ($sortedCosts->isEmpty()) {
            return [0.0, 0.0];
        }

        $p33 = $sortedCosts->get((int) floor($sortedCosts->count() * 0.33)) ?? $sortedCosts->last();
        $p66 = $sortedCosts->get((int) floor($sortedCosts->count() * 0.66)) ?? $sortedCosts->last();

        return [$p33, $p66];
    }

    private function costBand(float $cost, array $breakpoints): string
    {
        [$p33, $p66] = $breakpoints;

        return match (true) {
            $cost <= $p33 => 'low',
            $cost <= $p66 => 'medium',
            default => 'high',
        };
    }

    /**
     * @return array<int, array{label: string, cells: array<string, array{n: int, mortality_pct: ?float}>}>
     */
    private function buildCrossTab(Collection $cases, string $scoreField, callable $bandFn, array $costBreakpoints): array
    {
        $bandKeys = array_keys(str_contains($scoreField, 'news') ? self::NEWS2_BANDS : self::SOFA_BANDS);
        $labels = str_contains($scoreField, 'news') ? self::NEWS2_BANDS : self::SOFA_BANDS;
        $rows = [];

        foreach ($bandKeys as $bandKey) {
            $cells = [];

            foreach (array_keys(self::COST_BANDS) as $costKey) {
                $group = $cases->filter(function (SepsisCase $case) use ($bandFn, $scoreField, $bandKey, $costBreakpoints, $costKey): bool {
                    return $bandFn($case->{$scoreField}) === $bandKey
                        && $this->costBand((float) $case->total_cost, $costBreakpoints) === $costKey;
                });
                $n = $group->count();
                $deaths = $group->filter(fn (SepsisCase $case): bool => (bool) $case->deceased)->count();

                $cells[$costKey] = [
                    'n' => $n,
                    'mortality_pct' => $n > 0 ? round($deaths / $n * 100, 1) : null,
                ];
            }

            $rows[] = ['label' => $labels[$bandKey], 'cells' => $cells];
        }

        return $rows;
    }
}
