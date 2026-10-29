<?php

namespace App\Services;

use App\Models\SepsisCase;
use Illuminate\Support\Collection;

/**
 * Tiempo desde el tiempo cero hasta el control del foco, segmentado por tipo de foco
 * infeccioso. Servicio administrativo/analítico independiente de SepsisIndicatorService:
 * no calcula, no recalcula ni sustituye ninguno de los 6 indicadores institucionales.
 */
class SepsisSourceControlTimingService
{
    /**
     * @return array<int, array{focus: string, n: int, median_hours: ?float}>
     */
    public function medianHoursByFocus(?string $year = null, ?string $month = null): array
    {
        $query = SepsisCase::query()
            ->where('is_valid', true)
            ->where('is_cancelled', false)
            ->whereNotNull('activation_at')
            ->with(['sourceControlActions' => fn ($q) => $q->whereNotNull('performed_at')->orderBy('performed_at')]);

        if ($year) {
            $query->where('month', 'like', "{$year}/%");
        }

        if ($month) {
            $query->where('month', $month);
        }

        $cases = $query->get();

        return $cases
            ->groupBy(fn (SepsisCase $case): string => $case->infection_focus ?: 'Sin foco identificado')
            ->map(function (Collection $group, string $focus): array {
                $hours = $group
                    ->map(function (SepsisCase $case): ?float {
                        $firstControl = $case->sourceControlActions->first();

                        if (! $firstControl) {
                            return null;
                        }

                        return $case->activation_at->diffInMinutes($firstControl->performed_at) / 60;
                    })
                    ->filter(fn (?float $hours): bool => $hours !== null)
                    ->values();

                return [
                    'focus' => $focus,
                    'n' => $hours->count(),
                    'median_hours' => $hours->isNotEmpty() ? $this->median($hours) : null,
                ];
            })
            ->filter(fn (array $row): bool => $row['n'] > 0)
            ->sortByDesc('median_hours')
            ->values()
            ->all();
    }

    private function median(Collection $values): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();
        $middle = intdiv($count, 2);

        if ($count % 2 === 0) {
            return round(($sorted[$middle - 1] + $sorted[$middle]) / 2, 1);
        }

        return round($sorted[$middle], 1);
    }
}
