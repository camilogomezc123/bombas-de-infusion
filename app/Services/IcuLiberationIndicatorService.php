<?php

namespace App\Services;

use App\Models\IcuStay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Motor de indicadores del Programa ICU Liberation. Sigue el mismo método usado por
 * TepIndicatorService/SepsisIndicatorService: una sola lectura de estancias por período,
 * cálculo declarativo por indicador, sin dosis, órdenes ni decisiones clínicas automáticas
 * — solo mide lo que el equipo ya registró.
 */
class IcuLiberationIndicatorService
{
    public const GOALS = [
        'bundle_compliance_pct' => ['label' => 'Cumplimiento del bundle ABCDEF (por oportunidades)', 'target' => null, 'direction' => 'gte'],
        'pain_assessment_pct' => ['label' => 'Dolor evaluado', 'target' => null, 'direction' => 'gte'],
        'sat_realized_pct' => ['label' => 'SAT realizado entre elegibles (ventilados)', 'target' => null, 'direction' => 'gte'],
        'sbt_realized_pct' => ['label' => 'SBT realizado entre elegibles (ventilados)', 'target' => null, 'direction' => 'gte'],
        'sedation_goal_documented_pct' => ['label' => 'Objetivo de sedación documentado', 'target' => null, 'direction' => 'gte'],
        'delirium_assessment_pct' => ['label' => 'Evaluación de delirium realizada', 'target' => null, 'direction' => 'gte'],
        'delirium_prevalence_pct' => ['label' => 'Prevalencia de delirium', 'target' => null, 'direction' => 'lte'],
        'mobility_realized_pct' => ['label' => 'Movilidad realizada entre elegibles', 'target' => null, 'direction' => 'gte'],
        'ventilation_days_median' => ['label' => 'Días de ventilación mecánica (mediana)', 'target' => null, 'direction' => 'lte'],
        'icu_los_median_days' => ['label' => 'Estancia en UCI (mediana, días)', 'target' => null, 'direction' => 'lte'],
        'hospital_mortality_pct' => ['label' => 'Mortalidad hospitalaria', 'target' => null, 'direction' => 'lte'],
        'icu_mortality_pct' => ['label' => 'Mortalidad en UCI', 'target' => null, 'direction' => 'lte'],
        'reintubation_pct' => ['label' => 'Reintubación', 'target' => null, 'direction' => 'lte'],
        'unplanned_extubation_pct' => ['label' => 'Autoextubación', 'target' => null, 'direction' => 'lte'],
        'pics_followup_rate_pct' => ['label' => 'Seguimiento post-UCI iniciado', 'target' => null, 'direction' => 'gte'],
    ];

    public function dashboard(?string $year = null): array
    {
        $stays = $this->query($year)
            ->with([
                'painAssessments', 'satTrials', 'sbtTrials', 'sedationAssessments',
                'deliriumAssessments', 'mobilitySessions', 'familyEngagements', 'picsFollowups',
            ])
            ->get();

        $ventilated = $stays->where('mechanical_ventilation', true);
        $extubated = $ventilated->whereNotNull('extubation_at');
        $discharged = $stays->filter(fn (IcuStay $s): bool => $s->icu_discharge_at !== null || $s->hospital_discharge_at !== null);
        $deliriumEvaluated = $stays->filter(fn (IcuStay $s): bool => $s->deliriumAssessments->isNotEmpty());

        return [
            'stays' => $stays->count(),
            'ventilated' => $ventilated->count(),
            'bundle_compliance_pct' => $this->bundleCompliance($stays),
            'pain_assessment_pct' => $this->percentage($stays, fn (IcuStay $s) => $s->painAssessments->isNotEmpty()),
            'sat_realized_pct' => $this->percentage($ventilated, fn (IcuStay $s) => $s->satTrials->isNotEmpty()),
            'sbt_realized_pct' => $this->percentage($ventilated, fn (IcuStay $s) => $s->sbtTrials->isNotEmpty()),
            'sedation_goal_documented_pct' => $this->percentage($stays, fn (IcuStay $s) => $s->sedationAssessments->contains(fn ($a) => filled($a->goal_value))),
            'delirium_assessment_pct' => $this->percentage($stays, fn (IcuStay $s) => $s->deliriumAssessments->isNotEmpty()),
            'delirium_prevalence_pct' => $this->percentage($deliriumEvaluated, fn (IcuStay $s) => $s->deliriumAssessments->contains('result', 'positive')),
            'mobility_realized_pct' => $this->percentage($stays, fn (IcuStay $s) => $s->mobilitySessions->isNotEmpty()),
            'family_identified_pct' => $this->percentage($stays, fn (IcuStay $s) => $s->familyEngagements->isNotEmpty()),
            'ventilation_days_median' => $this->medianDays($ventilated, fn (IcuStay $s) => $this->ventilationDays($s)),
            'icu_los_median_days' => $this->medianDays($stays, fn (IcuStay $s) => $this->icuLosDays($s)),
            'hospital_mortality_pct' => $this->percentage($stays, fn (IcuStay $s) => $s->death_at !== null),
            'icu_mortality_pct' => $this->percentage($stays, fn (IcuStay $s) => $s->death_at !== null && $s->icu_discharge_at === null),
            'reintubation_pct' => $this->percentage($extubated, fn (IcuStay $s) => $s->reintubation_at !== null),
            'unplanned_extubation_pct' => $this->percentage($ventilated, fn (IcuStay $s) => (bool) $s->unplanned_extubation),
            'pics_followup_rate_pct' => $this->percentage($discharged, fn (IcuStay $s) => $s->picsFollowups->isNotEmpty()),
        ];
    }

    /**
     * Días desde el egreso hospitalario a partir de los cuales cada hito de PICS se
     * considera "vencido" si no hay un seguimiento con contacto logrado. Es una alerta
     * operativa (no un indicador de período): no depende del filtro por año.
     */
    private const PICS_CHECKPOINT_DUE_DAYS = [
        '48_72h' => 3, '7d' => 7, '30d' => 30, '3m' => 90, '6m' => 180, '12m' => 365,
    ];

    /**
     * Estancias egresadas y vivas con al menos un hito de PICS vencido (venció el plazo
     * y no hay un seguimiento con contact_achieved=true para ese hito). No sustituye el
     * criterio clínico sobre a quién corresponde seguir — solo señala plazos vencidos
     * sobre lo que el equipo ya decidió que aplicaba.
     */
    public function overduePicsFollowups(): int
    {
        $stays = IcuStay::query()
            ->whereHas('program', fn (Builder $q) => $q->where('code', 'ICULIB'))
            ->where('is_valid', true)->where('is_cancelled', false)
            ->whereNull('death_at')
            ->whereNotNull('hospital_discharge_at')
            ->with('picsFollowups')
            ->get();

        return $stays->filter(function (IcuStay $stay): bool {
            $achieved = $stay->picsFollowups->where('contact_achieved', true)->pluck('checkpoint');

            foreach (self::PICS_CHECKPOINT_DUE_DAYS as $checkpoint => $days) {
                if ($achieved->contains($checkpoint)) {
                    continue;
                }

                if ($stay->hospital_discharge_at->addDays($days)->isPast()) {
                    return true;
                }
            }

            return false;
        })->count();
    }

    /**
     * COMP-01/COMP-02: dosis del bundle por oportunidades, no "todo o nada". El sueño (G)
     * y la familia se miden como indicadores propios — no diluyen esta medida estricta de
     * los 4 componentes ABCDEF continuamente aplicables más SAT/SBT cuando hay ventilación.
     * Un componente marcado "no aplica" en field_status se excluye del denominador — no
     * se cuenta como incumplimiento (p. ej. movilidad correctamente contraindicada).
     */
    private function bundleCompliance(Collection $stays): ?float
    {
        $applicable = 0;
        $fulfilled = 0;

        foreach ($stays as $stay) {
            /** @var IcuStay $stay */
            $components = [
                'pain' => $stay->painAssessments->isNotEmpty(),
                'sedation' => $stay->sedationAssessments->isNotEmpty(),
                'delirium' => $stay->deliriumAssessments->isNotEmpty(),
                'mobility' => $stay->mobilitySessions->isNotEmpty(),
            ];

            if ($stay->mechanical_ventilation) {
                $components['sat'] = $stay->satTrials->isNotEmpty();
                $components['sbt'] = $stay->sbtTrials->isNotEmpty();
            }

            foreach ($components as $key => $done) {
                if ($stay->componentOverride($key) === 'not_applicable') {
                    continue;
                }

                $applicable++;
                $fulfilled += $done ? 1 : 0;
            }
        }

        return $applicable === 0 ? null : round(($fulfilled / $applicable) * 100, 1);
    }

    private function ventilationDays(IcuStay $stay): ?float
    {
        $end = $stay->extubation_at ?? $stay->icu_discharge_at ?? $stay->death_at;

        if (! $stay->ventilation_start_at || ! $end) {
            return null;
        }

        return round($stay->ventilation_start_at->diffInHours($end) / 24, 1);
    }

    private function icuLosDays(IcuStay $stay): ?float
    {
        if ($stay->icu_stay_days !== null) {
            return (float) $stay->icu_stay_days;
        }

        $end = $stay->icu_discharge_at ?? $stay->death_at;

        if (! $stay->admission_at || ! $end) {
            return null;
        }

        return round($stay->admission_at->diffInHours($end) / 24, 1);
    }

    private function query(?string $year): Builder
    {
        return IcuStay::query()
            ->whereHas('program', fn (Builder $q) => $q->where('code', 'ICULIB'))
            ->where('is_valid', true)
            ->where('is_cancelled', false)
            ->when($year, fn (Builder $q) => $q->whereYear('admission_at', $year));
    }

    private function percentage(Collection $items, callable $criterion): ?float
    {
        return $items->isEmpty() ? null : round($items->filter($criterion)->count() * 100 / $items->count(), 1);
    }

    private function medianDays(Collection $items, callable $valueResolver): ?float
    {
        $values = $items->map($valueResolver)->filter(fn (?float $v): bool => $v !== null)->sort()->values();

        return $values->isEmpty() ? null : round((float) $values->median(), 1);
    }
}
