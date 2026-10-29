<?php

namespace App\Services;

use App\Models\AcsCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AcsIndicatorService
{
    public const GOALS = [
        'ecg_10_pct' => ['label' => 'ECG interpretado ≤10 min', 'target' => 90.0, 'direction' => 'gte'],
        'fmc_device_direct_90_pct' => ['label' => 'FMC-dispositivo ≤90 min (directo)', 'target' => 90.0, 'direction' => 'gte'],
        'fmc_device_transfer_120_pct' => ['label' => 'FMC-dispositivo ≤120 min (traslado)', 'target' => 90.0, 'direction' => 'gte'],
        'nste_angio_24_pct' => ['label' => 'NSTE-ACS alto riesgo con angiografía ≤24 h', 'target' => 90.0, 'direction' => 'gte'],
        'grace_documented_pct' => ['label' => 'Riesgo GRACE medido y documentado', 'target' => 90.0, 'direction' => 'gte'],
        'rehab_referral_pct' => ['label' => 'Remisión a rehabilitación', 'target' => 85.0, 'direction' => 'gte'],
        'hospital_mortality_pct' => ['label' => 'Mortalidad hospitalaria', 'target' => null, 'direction' => 'lte'],
        'mace_30d_pct' => ['label' => 'MACE a 30 días', 'target' => null, 'direction' => 'lte'],
        'readmission_30d_pct' => ['label' => 'Reingreso a 30 días', 'target' => null, 'direction' => 'lte'],
    ];

    public function dashboard(?string $year = null, ?string $month = null): array
    {
        $cases = $this->query($year, $month)->with('dischargePlan')->get();

        $stemi = $cases->where('acs_type', 'stemi');
        $nste = $cases->whereIn('acs_type', ['nstemi', 'nste_acs', 'unstable_angina']);
        $directPci = $stemi->where('entry_route', 'direct')->filter(fn (AcsCase $case) => $case->first_medical_contact_at && $case->first_device_at);
        $transferPci = $stemi->where('entry_route', 'transfer')->filter(fn (AcsCase $case) => $case->first_medical_contact_at && $case->first_device_at);
        $ecgEligible = $cases->filter(fn (AcsCase $case) => ($case->first_medical_contact_at || $case->admission_at) && $case->ecg_interpreted_at);
        $nsteEligible = $nste->filter(fn (AcsCase $case) => $case->diagnosis_at && $case->angiography_at && $case->nste_strategy === 'early');

        return [
            'cases' => $cases->count(),
            'stemi_cases' => $stemi->count(),
            'nstemi_cases' => $cases->where('acs_type', 'nstemi')->count(),
            'ecg_10_pct' => $this->percentage($ecgEligible, fn (AcsCase $c) => $this->minutes($c->first_medical_contact_at ?? $c->admission_at, $c->ecg_interpreted_at) <= 10),
            'fmc_device_direct_90_pct' => $this->percentage($directPci, fn (AcsCase $c) => $this->minutes($c->first_medical_contact_at, $c->first_device_at) <= 90),
            'fmc_device_transfer_120_pct' => $this->percentage($transferPci, fn (AcsCase $c) => $this->minutes($c->first_medical_contact_at, $c->first_device_at) <= 120),
            'median_fmc_device' => $this->medianMinutes($stemi, 'first_medical_contact_at', 'first_device_at'),
            'median_door_ecg' => $this->medianMinutes($cases, 'admission_at', 'ecg_interpreted_at'),
            'nste_angio_24_pct' => $this->percentage($nsteEligible, fn (AcsCase $c) => $this->minutes($c->diagnosis_at, $c->angiography_at) <= 1440),
            'grace_documented_pct' => $this->percentage(
                $nste,
                fn (AcsCase $case) => $case->grace_score !== null
                    && filled($case->grace_risk_category)
                    && $case->grace_assessed_at !== null,
            ),
            'grace_analysis' => $this->graceAnalysis($nste),
            'rehab_referral_pct' => $this->percentage($cases->filter(fn (AcsCase $c) => $c->discharged_at), fn (AcsCase $c) => (bool) $c->dischargePlan?->rehabilitation_referral),
            'hospital_mortality_pct' => $this->percentage($cases, fn (AcsCase $c) => (bool) $c->death_at),
            'mace_30d_pct' => $this->percentage($cases->whereNotNull('mace_30d'), fn (AcsCase $c) => $c->mace_30d),
            'readmission_30d_pct' => $this->percentage($cases->whereNotNull('readmission_30d'), fn (AcsCase $c) => $c->readmission_30d),
        ];
    }

    public static function status(string $key, ?float $value): string
    {
        if ($value === null) {
            return 'gray';
        }
        $goal = self::GOALS[$key]['target'] ?? null;
        if ($goal === null) {
            return 'blue';
        }
        $meets = (self::GOALS[$key]['direction'] ?? 'gte') === 'gte' ? $value >= $goal : $value <= $goal;
        if ($meets) {
            return 'green';
        }

        return $value >= ($goal * .9) ? 'yellow' : 'red';
    }

    private function query(?string $year, ?string $month): Builder
    {
        return AcsCase::query()
            ->whereHas('program', fn (Builder $q) => $q->where('code', 'INFARTO'))
            ->where('is_valid', true)->where('is_cancelled', false)
            ->when($month, fn (Builder $q) => $q->whereYear('admission_at', substr($month, 0, 4))->whereMonth('admission_at', substr($month, 5, 2)))
            ->when(! $month && $year, fn (Builder $q) => $q->whereYear('admission_at', $year));
    }

    private function percentage(Collection $denominator, callable $criterion): ?float
    {
        if ($denominator->isEmpty()) {
            return null;
        }

        return round($denominator->filter($criterion)->count() * 100 / $denominator->count(), 1);
    }

    private function minutes($from, $to): int
    {
        return (int) $from->diffInMinutes($to);
    }

    private function medianMinutes(Collection $cases, string $from, string $to): ?float
    {
        $values = $cases->filter(fn (AcsCase $c) => $c->{$from} && $c->{$to})
            ->map(fn (AcsCase $c) => $this->minutes($c->{$from}, $c->{$to}))->sort()->values();

        return $values->isEmpty() ? null : round((float) $values->median(), 1);
    }

    /**
     * Análisis descriptivo: cruza la categoría GRACE documentada por el clínico
     * con mortalidad observada y estancias. No estima riesgo ni causalidad.
     *
     * @return array<string, array<string, int|float|null>>
     */
    private function graceAnalysis(Collection $cases): array
    {
        $labels = [
            'low' => 'Riesgo bajo',
            'intermediate' => 'Riesgo intermedio',
            'high' => 'Riesgo alto',
            'not_documented' => 'Sin GRACE completo',
        ];

        return $cases
            ->groupBy(fn (AcsCase $case): string => filled($case->grace_risk_category)
                ? $case->grace_risk_category
                : 'not_documented')
            ->map(function (Collection $group, string $category) use ($labels): array {
                $hospitalStay = $group->pluck('hospital_stay_days')->filter(fn ($value) => $value !== null);
                $icuStay = $group->pluck('icu_stay_days')->filter(fn ($value) => $value !== null);
                $deaths = $group->filter(fn (AcsCase $case): bool => $case->death_at !== null)->count();

                return [
                    'label' => $labels[$category] ?? $category,
                    'cases' => $group->count(),
                    'deaths' => $deaths,
                    'mortality_pct' => round($deaths * 100 / $group->count(), 1),
                    'median_hospital_stay' => $hospitalStay->isEmpty() ? null : round((float) $hospitalStay->median(), 1),
                    'median_icu_stay' => $icuStay->isEmpty() ? null : round((float) $icuStay->median(), 1),
                ];
            })
            ->all();
    }
}
