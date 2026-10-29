<?php

namespace App\Services;

use App\Models\SepsisCase;

/**
 * Análisis de costo de la atención de Sepsis. Es un servicio administrativo/financiero
 * independiente de SepsisIndicatorService: no calcula, no reemplaza ni reutiliza las
 * fórmulas de los 6 indicadores institucionales, solo agrega el campo total_cost.
 */
class SepsisCostService
{
    private function baseQuery(?string $year, ?string $month)
    {
        $query = SepsisCase::query()
            ->where('is_valid', true)
            ->where('is_cancelled', false)
            ->whereNotNull('total_cost')
            ->with('patient');

        if ($year) {
            $query->where('month', 'like', "{$year}/%");
        }

        if ($month) {
            $query->where('month', $month);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(?string $year = null, ?string $month = null): array
    {
        $cases = $this->baseQuery($year, $month)->get();

        if ($cases->isEmpty()) {
            return [
                'count' => 0,
                'total_cost' => null,
                'min_cost' => null,
                'max_cost' => null,
                'avg_cost' => null,
                'avg_cost_per_stay_day' => null,
                'avg_cost_deceased' => null,
                'avg_cost_alive' => null,
                'count_deceased' => 0,
                'count_alive' => 0,
                'top_cases' => collect(),
            ];
        }

        $costs = $cases->map(fn (SepsisCase $case): float => (float) $case->total_cost);
        $deceased = $cases->filter(fn (SepsisCase $case): bool => (bool) $case->deceased);
        $alive = $cases->reject(fn (SepsisCase $case): bool => (bool) $case->deceased);

        $totalStayDays = $cases->sum(fn (SepsisCase $case): float => $this->stayDays($case));
        $totalCost = $costs->sum();

        return [
            'count' => $cases->count(),
            'total_cost' => $totalCost,
            'min_cost' => $costs->min(),
            'max_cost' => $costs->max(),
            'avg_cost' => $costs->avg(),
            'avg_cost_per_stay_day' => $totalStayDays > 0 ? $totalCost / $totalStayDays : null,
            'avg_cost_deceased' => $deceased->isNotEmpty() ? $deceased->avg(fn (SepsisCase $case): float => (float) $case->total_cost) : null,
            'avg_cost_alive' => $alive->isNotEmpty() ? $alive->avg(fn (SepsisCase $case): float => (float) $case->total_cost) : null,
            'count_deceased' => $deceased->count(),
            'count_alive' => $alive->count(),
            'top_cases' => $cases->sortByDesc('total_cost')->take(10)->values()
                ->each(function (SepsisCase $case): void {
                    $case->stay_days = $this->stayDays($case);
                }),
        ];
    }

    private function stayDays(SepsisCase $case): float
    {
        return (float) ($case->er_stay_days ?? 0)
            + (float) ($case->clinic_stay_days ?? 0)
            + (float) ($case->uci_stay_days ?? 0);
    }
}
