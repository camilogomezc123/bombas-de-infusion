<?php

namespace App\Services;

use App\Models\AcvCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AcvIndicatorService
{
    public function monthly(?string $year = null): array
    {
        return $this->cases($year)
            ->groupBy('month')
            ->map(fn (Collection $cases, string $month): array => $this->calculateMonth($month, $cases))
            ->values()
            ->all();
    }

    public function annual(string $year): ?array
    {
        $cases = $this->cases($year);

        return $cases->isEmpty()
            ? null
            : $this->calculateMonth($year, $cases);
    }

    public function quarterly(string $year, int $quarter): ?array
    {
        if ($quarter < 1 || $quarter > 4) {
            return null;
        }

        $firstMonth = (($quarter - 1) * 3) + 1;
        $months = range($firstMonth, $firstMonth + 2);
        $cases = $this->cases($year)
            ->filter(fn (AcvCase $case): bool => in_array((int) substr($case->month, 5, 2), $months, true))
            ->values();

        return $cases->isEmpty()
            ? null
            : $this->calculateMonth("{$year} Q{$quarter}", $cases);
    }

    public function years(): array
    {
        return AcvCase::query()
            ->whereNotNull('month')
            ->select('month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn (string $month): string => substr($month, 0, 4))
            ->unique()
            ->values()
            ->all();
    }

    private function cases(?string $year = null): Collection
    {
        return AcvCase::query()
            ->where('is_cancelled', false)
            ->whereNotNull('month')
            ->when($year, fn ($query) => $query->where('month', 'like', "{$year}/%"))
            ->orderBy('month')
            ->get();
    }

    private function calculateMonth(string $month, Collection $cases): array
    {
        $ischemic = $cases->filter(fn (AcvCase $case): bool => $this->strokeCategory($case) === 'ischemic');
        $tia = $cases->filter(fn (AcvCase $case): bool => $this->strokeCategory($case) === 'tia');
        $hemorrhagic = $cases->filter(fn (AcvCase $case): bool => $this->strokeCategory($case) === 'hemorrhagic');
        $mimics = $cases->filter(fn (AcvCase $case): bool => $this->strokeCategory($case) === 'mimic');
        $venousThrombosis = $cases->filter(fn (AcvCase $case): bool => $this->strokeCategory($case) === 'venous_thrombosis');
        $inWindow = $cases->filter(fn (AcvCase $case): bool => $this->rawBoolean($case, 'Ventana trombolisis') === true);
        $outsideWindow = $cases->filter(fn (AcvCase $case): bool => $this->rawBoolean($case, 'Ventana trombolisis') === false);
        $thrombolysed = $cases->where('thrombolysed', true);
        $thrombectomy = $cases->where('thrombectomy', true);
        $treated = $cases->filter(fn (AcvCase $case): bool => $this->rawBoolean($case, 'Tratado') === true);
        $successfulRecanalization = $thrombectomy
            ->filter(fn (AcvCase $case): bool => $this->isSuccessfulRecanalization($case));
        $recanalizationMeasured = $thrombectomy
            ->filter(fn (AcvCase $case): bool => $this->ticiGrade($case) !== null);
        $imagingRecorded = $cases->filter(
            fn (AcvCase $case): bool => filled($case->imaging_at)
                || filled(data_get($case->clinical_data, 'imaging_type'))
                || filled($this->rawValue($case, 'Imagen realizada'))
        );
        $nonInpatientIschemic = $ischemic->reject(
            fn (AcvCase $case): bool => $this->isInpatientStroke($case),
        );
        $needleEligible = $nonInpatientIschemic->where('thrombolysed', true);
        $groinEligible = $nonInpatientIschemic->where('thrombectomy', true);
        $recanalizedIschemic = $ischemic->filter(
            fn (AcvCase $case): bool => $case->thrombolysed || $case->thrombectomy,
        );
        $dysphagiaEligible = $ischemic->merge($hemorrhagic);
        $dysphagiaScreened = $dysphagiaEligible->filter(
            fn (AcvCase $case): bool => filled($case->speech_therapy_at)
                || filled($this->rawValue($case, 'CumpleFono')),
        );

        $doorImage = $this->durations($cases, 'arrival_at', 'imaging_at', 1440);
        $doorNeedle = $this->durations($thrombolysed, 'arrival_at', 'thrombolysis_at', 1440);
        $doorGroin = $this->durations($thrombectomy, 'arrival_at', 'groin_puncture_at', 1440);
        $groinRecanalization = $this->durations($thrombectomy, 'groin_puncture_at', 'revascularization_at', 1440);
        $doorSpeech = $this->durations($cases, 'arrival_at', 'speech_therapy_at', 10080);

        return [
            'month' => $month,
            'total' => $cases->count(),
            'outside_window' => $outsideWindow->count(),
            'in_window' => $inWindow->count(),
            'mimics' => $mimics->count(),
            'door_image' => $this->median($doorImage),
            'thrombolysis' => $thrombolysed->count(),
            'door_needle' => $this->median($doorNeedle),
            'needle_60' => $this->compliance($doorNeedle, 60),
            'needle_45' => $this->compliance($doorNeedle, 45),
            'thrombectomy' => $thrombectomy->count(),
            'door_groin' => $this->median($doorGroin),
            'groin_120' => $this->compliance($doorGroin, 120),
            'groin_90' => $this->compliance($doorGroin, 90),
            'successful_recanalization' => $this->percentage(
                $successfulRecanalization->count(),
                $recanalizationMeasured->count(),
            ),
            'imaging_compliance' => $this->percentage($imagingRecorded->count(), $cases->count()),
            'door_speech' => $this->median($doorSpeech),
            'speech_compliance' => $this->compliance($doorSpeech, 2880),
            'ischemic' => $ischemic->count(),
            'tia' => $tia->count(),
            'hemorrhagic' => $hemorrhagic->count(),
            'hsa' => $cases->filter(fn (AcvCase $case): bool => $this->normalizedStrokeType($case) === 'hemorragia subaracnoidea')->count(),
            'hic' => $cases->filter(fn (AcvCase $case): bool => $this->normalizedStrokeType($case) === 'hemorragia intracerebral')->count(),
            'venous_thrombosis' => $venousThrombosis->count(),
            'thrombolysis_ischemic' => $this->percentage($thrombolysed->intersect($ischemic)->count(), $ischemic->count()),
            'eligible_ischemic' => $inWindow->intersect($ischemic)->count(),
            'eligible_thrombolysed' => $this->percentage(
                $thrombolysed->intersect($ischemic)->count(),
                $inWindow->intersect($ischemic)->count(),
            ),
            'treated' => $treated->count(),
            'treated_window' => $this->percentage(
                $treated->intersect($inWindow)->count(),
                $inWindow->count(),
            ),
            'groin_recanalization' => $this->median($groinRecanalization),
            'ischemic_deaths' => $ischemic->where('deceased', true)->count(),
            'hemorrhagic_deaths' => $hemorrhagic->where('deceased', true)->count(),
            'ischemic_mortality' => $this->percentage($ischemic->where('deceased', true)->count(), $ischemic->count()),
            'hemorrhagic_mortality' => $this->percentage($hemorrhagic->where('deceased', true)->count(), $hemorrhagic->count()),
            'hemorrhagic_transformation' => $cases->where('hemorrhagic_transformation', true)->count(),
            'treated_hemorrhage' => $this->percentage(
                $treated->where('hemorrhagic_transformation', true)->count(),
                $treated->count(),
            ),
            'angels_metrics' => [
                $this->angelsMetric(
                    'door_to_needle_60',
                    '% de pacientes isquémicos tratados con tiempo puerta-aguja ≤ 60 minutos',
                    $this->countWithinMinutes($needleEligible, 'arrival_at', 'thrombolysis_at', 60),
                    $needleEligible->count(),
                    50,
                    75,
                    75,
                ),
                $this->angelsMetric(
                    'door_to_needle_45',
                    '% de pacientes isquémicos tratados con tiempo puerta-aguja ≤ 45 minutos',
                    $this->countWithinMinutes($needleEligible, 'arrival_at', 'thrombolysis_at', 45),
                    $needleEligible->count(),
                    null,
                    null,
                    50,
                ),
                $this->angelsMetric(
                    'door_to_groin_120',
                    '% de pacientes isquémicos tratados con tiempo puerta-ingle ≤ 120 minutos',
                    $this->countWithinMinutes($groinEligible, 'arrival_at', 'groin_puncture_at', 120),
                    $groinEligible->count(),
                    50,
                    75,
                    75,
                ),
                $this->angelsMetric(
                    'door_to_groin_90',
                    '% de pacientes isquémicos tratados con tiempo puerta-ingle ≤ 90 minutos',
                    $this->countWithinMinutes($groinEligible, 'arrival_at', 'groin_puncture_at', 90),
                    $groinEligible->count(),
                    null,
                    null,
                    50,
                ),
                $this->angelsMetric(
                    'recanalization_rate',
                    '% de pacientes isquémicos sometidos a algún procedimiento de recanalización',
                    $recanalizedIschemic->count(),
                    $ischemic->count(),
                    5,
                    15,
                    25,
                ),
                $this->angelsMetric(
                    'ct_mr_imaging',
                    '% de pacientes con sospecha de ACV sometidos a CT o MR',
                    $imagingRecorded->count(),
                    $cases->count(),
                    80,
                    85,
                    90,
                    'La base actual no identifica traslados desde otro hospital; se incluyen todos los casos.',
                ),
                $this->angelsMetric(
                    'dysphagia_screening',
                    '% de pacientes con ACV evaluados para disfagia',
                    $dysphagiaScreened->count(),
                    $dysphagiaEligible->count(),
                    80,
                    85,
                    90,
                ),
                $this->unavailableAngelsMetric(
                    'af_anticoagulants',
                    '% de pacientes isquémicos/TIA con fibrilación auricular y anticoagulante al egreso',
                    80,
                    85,
                    90,
                    'Faltan fibrilación auricular y anticoagulante indicado al egreso.',
                ),
                $this->unavailableAngelsMetric(
                    'non_af_antithrombotics',
                    '% de pacientes isquémicos/TIA sin fibrilación auricular y antitrombótico al egreso',
                    80,
                    85,
                    90,
                    'Faltan fibrilación auricular y antitrombótico indicado al egreso.',
                ),
                $this->unavailableAngelsMetric(
                    'stroke_unit_icu',
                    'Pacientes tratados en unidad de ACV o UCI durante la hospitalización',
                    null,
                    null,
                    1,
                    'La base actual no registra estancia en unidad de ACV o UCI.',
                ),
            ],
        ];
    }

    private function countWithinMinutes(
        Collection $cases,
        string $start,
        string $end,
        int $maximum,
    ): int {
        return $cases->filter(function (AcvCase $case) use ($start, $end, $maximum): bool {
            if (blank($case->{$start}) || blank($case->{$end})) {
                return false;
            }

            $minutes = (int) $case->{$start}->diffInMinutes($case->{$end});

            return $minutes >= 0 && $minutes <= $maximum;
        })->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function angelsMetric(
        string $key,
        string $label,
        int $eligibleCases,
        int $totalCases,
        ?float $gold,
        ?float $platinum,
        ?float $diamond,
        ?string $note = null,
    ): array {
        $value = $this->percentage($eligibleCases, $totalCases);

        return [
            'key' => $key,
            'label' => $label,
            'status' => $this->awardLevel($value, $gold, $platinum, $diamond),
            'value' => $value,
            'total_cases' => $totalCases,
            'eligible_cases' => $eligibleCases,
            'gold' => $gold,
            'platinum' => $platinum,
            'diamond' => $diamond,
            'available' => true,
            'note' => $note,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailableAngelsMetric(
        string $key,
        string $label,
        ?float $gold,
        ?float $platinum,
        ?float $diamond,
        string $note,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'status' => null,
            'value' => null,
            'total_cases' => null,
            'eligible_cases' => null,
            'gold' => $gold,
            'platinum' => $platinum,
            'diamond' => $diamond,
            'available' => false,
            'note' => $note,
        ];
    }

    private function awardLevel(
        ?float $value,
        ?float $gold,
        ?float $platinum,
        ?float $diamond,
    ): ?string {
        if ($value === null) {
            return null;
        }

        foreach ([
            'Diamante' => $diamond,
            'Platino' => $platinum,
            'Oro' => $gold,
        ] as $level => $minimum) {
            if ($minimum !== null && $value >= $minimum) {
                return $level;
            }
        }

        return null;
    }

    private function isInpatientStroke(AcvCase $case): bool
    {
        return data_get($case->clinical_data, 'inpatient_stroke') === true
            || $this->rawBoolean($case, 'ACV estando hospitalizado') === true;
    }

    private function durations(Collection $cases, string $start, string $end, int $maximum): array
    {
        return $cases
            ->filter(fn (AcvCase $case): bool => filled($case->{$start}) && filled($case->{$end}))
            ->map(fn (AcvCase $case): int => (int) $case->{$start}->diffInMinutes($case->{$end}))
            ->filter(fn (int $minutes): bool => $minutes >= 0 && $minutes <= $maximum)
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

        $middle = intdiv($count, 2);

        return $count % 2
            ? (int) $values[$middle]
            : (int) round(($values[$middle - 1] + $values[$middle]) / 2);
    }

    private function compliance(array $durations, int $maximum): ?float
    {
        return $this->percentage(
            count(array_filter($durations, fn (int $minutes): bool => $minutes <= $maximum)),
            count($durations),
        );
    }

    private function percentage(int $numerator, int $denominator): ?float
    {
        return $denominator === 0 ? null : round(($numerator / $denominator) * 100, 1);
    }

    private function rawBoolean(AcvCase $case, string $key): ?bool
    {
        $value = $this->rawValue($case, $key);

        if ($value === null || $value === '') {
            return null;
        }

        return match (mb_strtolower(trim((string) $value))) {
            'sí', 'si', 'true', '1', 'x' => true,
            'no', 'false', '0' => false,
            default => null,
        };
    }

    private function rawValue(AcvCase $case, string $key): mixed
    {
        return data_get($case->clinical_data, "excel.{$key}");
    }

    private function isSuccessfulRecanalization(AcvCase $case): bool
    {
        $grade = $this->ticiGrade($case);

        return $grade !== null && $grade >= 2.5;
    }

    private function ticiGrade(AcvCase $case): ?float
    {
        $value = data_get($case->clinical_data, 'tici') ?? $this->rawValue($case, 'TICI');

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $normalized = mb_strtolower(trim((string) $value));

        if (preg_match('/([0-3])\s*([abc])?/', $normalized, $matches) !== 1) {
            return null;
        }

        return (float) $matches[1] + match ($matches[2] ?? '') {
            'a' => 0.25,
            'b' => 0.5,
            'c' => 0.75,
            default => 0.0,
        };
    }

    private function strokeCategory(AcvCase $case): string
    {
        $type = $this->normalizedStrokeType($case);

        return match (true) {
            $type === 'isquemico' => 'ischemic',
            in_array($type, ['tia', 'ataque isquemico transitorio (ait)'], true) => 'tia',
            in_array($type, ['hemorragico', 'hemorragia subaracnoidea', 'hemorragia intracerebral', 'hsa', 'hic'], true) => 'hemorrhagic',
            in_array($type, ['imitador del ictus', 'imitador'], true) => 'mimic',
            in_array($type, ['trombosis venosa', 'trombosis venosa cerebral'], true) => 'venous_thrombosis',
            default => 'other',
        };
    }

    private function normalizedStrokeType(AcvCase $case): string
    {
        return mb_strtolower(Str::ascii(trim((string) $case->stroke_type)));
    }
}
