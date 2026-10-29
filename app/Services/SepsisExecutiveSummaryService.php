<?php

namespace App\Services;

use App\Models\AssessmentFinding;
use App\Models\ClinicalProgram;
use App\Models\CommitteeMeeting;
use App\Models\CorrectiveAction;
use App\Models\EvidenceDocument;
use App\Models\MeasurableElement;
use App\Models\MeetingAction;
use App\Models\SepsisCase;
use App\Models\SepsisSafetyEvent;
use App\Models\StaffCompetency;
use Illuminate\Support\Carbon;

class SepsisExecutiveSummaryService
{
    public function summary(): array
    {
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->first();
        $indicatorSummary = app(SepsisIndicatorService::class)->summary(now()->format('Y'));
        $goalResults = collect(SepsisIndicatorService::GOALS)
            ->map(fn (array $goal, string $key): ?bool => SepsisIndicatorService::meetsGoal(
                $key,
                $indicatorSummary[$key] ?? null,
            ));

        return [
            'program_status' => $program?->status ?? 'not_configured',
            'compliance_percentage' => round((float) MeasurableElement::query()->avg('progress_percentage'), 1),
            'pending_elements' => MeasurableElement::query()->where('compliance_status', 'not_evaluated')->count(),
            'valid_evidence' => EvidenceDocument::query()
                ->where(fn ($query) => $query->whereNull('expires_on')->orWhere('expires_on', '>=', today()))
                ->count(),
            'expired_evidence' => EvidenceDocument::query()->where('expires_on', '<', today())->count(),
            'evidence_expiring_soon' => EvidenceDocument::query()
                ->whereBetween('expires_on', [today(), today()->addDays(30)])->count(),
            'competencies_expiring_soon' => StaffCompetency::query()
                ->whereBetween('expires_on', [today(), today()->addDays(30)])->count(),
            'competencies_expired' => StaffCompetency::query()->where('expires_on', '<', today())->count(),
            'open_findings' => AssessmentFinding::query()->where('status', 'open')->count(),
            'overdue_actions' => CorrectiveAction::query()
                ->whereNotIn('status', ['completed', 'cancelled'])->where('due_on', '<', today())->count(),
            'completed_meetings' => CommitteeMeeting::query()->where('status', 'completed')->count(),
            'open_commitments' => MeetingAction::query()->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'period_cases' => SepsisCase::query()
                ->where('month', now()->format('Y/m'))->where('is_cancelled', false)->count(),
            'open_safety_events' => SepsisSafetyEvent::query()->where('status', '!=', 'closed')->count(),
            'indicators_on_target' => $goalResults->filter(fn (?bool $value): bool => $value === true)->count(),
            'indicators_off_target' => $goalResults->filter(fn (?bool $value): bool => $value === false)->count(),
            'data_completeness_percentage' => $this->dataCompletenessPercentage(),
            'readiness' => $this->readinessTracking(),
        ];
    }

    /**
     * Sostenibilidad de la actividad del programa: meses consecutivos con casos
     * registrados y volumen acumulado de pacientes gestionados bajo la guía. Se apoya
     * en SepsisIndicatorService::monthly() (misma fuente que los 6 indicadores) para no
     * calcular una segunda versión del conteo de casos por mes.
     *
     * @return array{streak_months: int, cumulative_cases: int, threshold_cases: int, threshold_months: int, meets_case_threshold: bool, meets_month_threshold: bool}
     */
    private function readinessTracking(): array
    {
        $rows = app(SepsisIndicatorService::class)->monthly();

        $thresholdCases = 25;
        $thresholdMonths = 6;

        $cumulativeCases = collect($rows)->sum('total');

        // La racha se cuenta hacia atrás desde el último mes con datos reportados
        // (no desde el mes calendario actual, que puede seguir en curso y aún no
        // tener casos cargados sin que eso implique una racha rota).
        $streak = 0;
        if (! empty($rows)) {
            $expected = Carbon::createFromFormat('Y/m', end($rows)['month'])->startOfMonth();
            foreach (array_reverse($rows) as $row) {
                if ($row['month'] !== $expected->format('Y/m') || $row['total'] <= 0) {
                    break;
                }
                $streak++;
                $expected = $expected->subMonthNoOverflow();
            }
        }

        return [
            'streak_months' => $streak,
            'cumulative_cases' => $cumulativeCases,
            'threshold_cases' => $thresholdCases,
            'threshold_months' => $thresholdMonths,
            'meets_case_threshold' => $cumulativeCases >= $thresholdCases,
            'meets_month_threshold' => $streak >= $thresholdMonths,
        ];
    }

    /**
     * Distribuciones descriptivas del año actual (conteos simples, no indicadores):
     * servicio de origen, foco infeccioso, adquisición comunitaria/hospitalaria y
     * población especial. No calcula tasas ni reemplaza SepsisIndicatorService.
     *
     * @return array<string, array<string, int>>
     */
    public function distributions(): array
    {
        $cases = SepsisCase::query()
            ->where('is_valid', true)
            ->where('is_cancelled', false)
            ->where('month', 'like', now()->format('Y').'/%')
            ->get(['origin_service', 'infection_focus', 'acquisition_type', 'special_population']);

        $originService = $cases->groupBy(fn (SepsisCase $case) => $case->origin_service ?: 'Sin dato')
            ->map->count()->sortDesc()->all();

        $focus = $cases->groupBy(fn (SepsisCase $case) => $case->infection_focus ?: 'Sin dato')
            ->map->count()->sortDesc()->all();

        $acquisition = $cases->groupBy(fn (SepsisCase $case) => match ($case->acquisition_type) {
            'community' => 'Comunitaria',
            'hospital' => 'Hospitalaria',
            default => 'Sin dato',
        })->map->count()->all();

        $specialPopulation = collect();
        foreach ($cases as $case) {
            $populations = $case->special_population ?: [];
            if (empty($populations)) {
                $specialPopulation['Sin dato'] = ($specialPopulation['Sin dato'] ?? 0) + 1;

                continue;
            }
            foreach ($populations as $population) {
                $specialPopulation[$population] = ($specialPopulation[$population] ?? 0) + 1;
            }
        }

        return [
            'origin_service' => $originService,
            'infection_focus' => $focus,
            'acquisition_type' => $acquisition,
            'special_population' => $specialPopulation->sortDesc()->all(),
        ];
    }

    /**
     * Porcentaje de completitud de registro clínico de los casos del periodo actual.
     * Es una métrica de calidad del dato (Fase 5), distinta de los 6 indicadores
     * institucionales: no recalcula ni sustituye nada de SepsisIndicatorService.
     */
    private function dataCompletenessPercentage(): float
    {
        $columns = [...array_keys(SepsisCase::KEY_TRACKING_FIELDS), 'field_status'];

        $cases = SepsisCase::query()
            ->where('month', now()->format('Y/m'))
            ->where('is_cancelled', false)
            ->get($columns);

        if ($cases->isEmpty()) {
            return 0.0;
        }

        return round($cases->map(fn (SepsisCase $case): float => $case->completenessSummary()['percentage'])->avg(), 1);
    }
}
