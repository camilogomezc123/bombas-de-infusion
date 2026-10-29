<?php

namespace App\Services;

use App\Enums\CaseStatus;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PicsCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Trazabilidad del uso real del portal (/portal) por parte del paciente y la familia
 * — cuántas veces entran, si escriben el diario, si reportan avances, si diligencian
 * "Cómo me siento", si piden ayuda y qué tan rápido se les responde, y qué tan
 * preparado está el egreso (plan interdisciplinario, ruta del cuidador, preparación
 * para el alta, medicamentos conciliados, monitoreo en casa, educación vista). Es un
 * eje de medición distinto al de PicsIndicatorService (que mide los instrumentos
 * clínicos PICS, no el comportamiento de uso de la plataforma).
 */
class PortalEngagementService
{
    /**
     * Instantánea de uso del portal para un caso puntual. Asume que las relaciones ya
     * vienen precargadas (patient, caregiverAuthorizations.caregiver, diaryEntries,
     * recoveryGoals.progressReports, followups, supportRequests, recoveryPassport,
     * carePlan, caregiverJourneySteps, dischargeReadinessCheck.items,
     * medicationReconciliation.items, homeMonitoringReadings, educationAssignments) —
     * no dispara consultas adicionales por caso.
     *
     * @return array<string, mixed>
     */
    public function caseSnapshot(PicsCase $case): array
    {
        $patient = $case->patient;
        $authorization = $case->caregiverAuthorizations->whereNull('revoked_at')->first();
        $caregiver = $authorization?->caregiver;

        $diaryCount = $case->diaryEntries->count();

        $goalReports = $case->recoveryGoals->flatMap->progressReports;
        $goalReportsByPatient = $goalReports->where('reporter_type', Patient::class)->count();
        $goalReportsByCaregiver = $goalReports->where('reporter_type', Caregiver::class)->count();

        $selfReportedFollowups = $case->followups->whereNotNull('submitted_by_type');
        $confirmedFollowups = $selfReportedFollowups->whereNotNull('confirmed_at');

        $requests = $case->supportRequests;
        $answeredRequests = $requests->whereNotNull('responded_at');
        $avgResponseHours = $answeredRequests->isEmpty() ? null : round(
            $answeredRequests->avg(fn ($r) => $r->created_at->diffInMinutes($r->responded_at) / 60),
            1,
        );

        $passport = $case->recoveryPassport;
        $passportStatus = match (true) {
            $passport === null || $passport->reported_at === null => 'sin_diligenciar',
            $passport->is_confirmed => 'confirmado',
            default => 'reportado',
        };

        $journeySteps = $case->caregiverJourneySteps;
        $dischargeCheck = $case->dischargeReadinessCheck;
        $medicationReconciliation = $case->medicationReconciliation;
        $educationAssignments = $case->educationAssignments;

        $lastActivity = collect([
            $patient?->last_login_at,
            $caregiver?->last_login_at,
            $case->diaryEntries->max('created_at'),
            $goalReports->max('reported_at'),
            $selfReportedFollowups->max('followed_up_at'),
            $requests->max('created_at'),
            $passport?->reported_at,
        ])->filter()->map(fn ($d) => Carbon::parse($d))->sort()->last();

        return [
            'care_plan_exists' => $case->carePlan !== null,
            'caregiver_journey_total' => $journeySteps->count(),
            'caregiver_journey_completed' => $journeySteps->whereNotNull('reported_at')->count(),
            'discharge_readiness_percentage' => $dischargeCheck?->readinessSummary()['percentage'],
            'medication_reconciliation_status' => ($medicationReconciliation && $medicationReconciliation->items->isNotEmpty()) ? 'conciliado' : 'sin_conciliar',
            'home_monitoring_readings_count' => $case->homeMonitoringReadings->count(),
            'education_assigned_count' => $educationAssignments->count(),
            'education_viewed_count' => $educationAssignments->whereNotNull('viewed_at')->count(),
            'caregiver_authorized' => $authorization !== null,
            'caregiver_authorized_at' => $authorization?->authorized_at,
            'caregiver_last_login_at' => $caregiver?->last_login_at,
            'patient_has_account' => filled($patient?->email),
            'patient_last_login_at' => $patient?->last_login_at,
            'diary_entries_count' => $diaryCount,
            'goal_reports_total' => $goalReports->count(),
            'goal_reports_by_patient' => $goalReportsByPatient,
            'goal_reports_by_caregiver' => $goalReportsByCaregiver,
            'wellbeing_self_reports_count' => $selfReportedFollowups->count(),
            'wellbeing_confirmed_count' => $confirmedFollowups->count(),
            'support_requests_total' => $requests->count(),
            'support_requests_answered' => $answeredRequests->count(),
            'support_requests_avg_response_hours' => $avgResponseHours,
            'passport_status' => $passportStatus,
            'last_portal_activity_at' => $lastActivity,
        ];
    }

    /**
     * Resumen institucional a partir de una colección de casos (ya con las mismas
     * relaciones precargadas que espera caseSnapshot()).
     *
     * @param  Collection<int, PicsCase>  $cases
     * @return array<string, mixed>
     */
    public function aggregate(Collection $cases): array
    {
        if ($cases->isEmpty()) {
            return [
                'total_cases' => 0, 'caregiver_authorized_pct' => null, 'any_login_pct' => null,
                'diary_activity_pct' => null, 'wellbeing_self_report_pct' => null,
                'avg_goal_reports_per_case' => null, 'patient_report_share_pct' => null,
                'avg_support_response_hours' => null, 'passport_confirmed_pct' => null,
                'care_plan_pct' => null, 'medication_reconciliation_pct' => null,
                'discharge_readiness_avg_pct' => null, 'education_viewed_pct' => null,
                'caregiver_journey_avg_pct' => null,
            ];
        }

        $snapshots = $cases->map(fn (PicsCase $case) => $this->caseSnapshot($case));

        $withLogin = $snapshots->filter(fn (array $s) => $s['patient_last_login_at'] || $s['caregiver_last_login_at']);
        $withDiary = $snapshots->filter(fn (array $s) => $s['diary_entries_count'] > 0);
        $withWellbeing = $snapshots->filter(fn (array $s) => $s['wellbeing_self_reports_count'] > 0);
        $totalGoalReports = $snapshots->sum('goal_reports_total');
        $patientGoalReports = $snapshots->sum('goal_reports_by_patient');
        $responseTimes = $snapshots->pluck('support_requests_avg_response_hours')->filter();
        $confirmedPassports = $snapshots->filter(fn (array $s) => $s['passport_status'] === 'confirmado');
        $withCarePlan = $snapshots->where('care_plan_exists', true);
        $withMedicationReconciliation = $snapshots->filter(fn (array $s) => $s['medication_reconciliation_status'] === 'conciliado');
        $dischargeReadinessValues = $snapshots->pluck('discharge_readiness_percentage')->filter(fn ($v) => $v !== null);
        $totalEducationAssigned = $snapshots->sum('education_assigned_count');
        $totalEducationViewed = $snapshots->sum('education_viewed_count');
        $casesWithJourney = $snapshots->filter(fn (array $s) => $s['caregiver_journey_total'] > 0);
        $journeyAvgPct = $casesWithJourney->isEmpty() ? null : round(
            $casesWithJourney->avg(fn (array $s) => $this->percentage($s['caregiver_journey_completed'], $s['caregiver_journey_total'])),
            1,
        );

        return [
            'total_cases' => $cases->count(),
            'caregiver_authorized_pct' => $this->percentage($snapshots->where('caregiver_authorized', true)->count(), $cases->count()),
            'any_login_pct' => $this->percentage($withLogin->count(), $cases->count()),
            'diary_activity_pct' => $this->percentage($withDiary->count(), $cases->count()),
            'wellbeing_self_report_pct' => $this->percentage($withWellbeing->count(), $cases->count()),
            'avg_goal_reports_per_case' => round($totalGoalReports / $cases->count(), 1),
            'patient_report_share_pct' => $this->percentage($patientGoalReports, $totalGoalReports),
            'care_plan_pct' => $this->percentage($withCarePlan->count(), $cases->count()),
            'medication_reconciliation_pct' => $this->percentage($withMedicationReconciliation->count(), $cases->count()),
            'discharge_readiness_avg_pct' => $dischargeReadinessValues->isEmpty() ? null : round($dischargeReadinessValues->avg(), 1),
            'education_viewed_pct' => $this->percentage($totalEducationViewed, $totalEducationAssigned),
            'caregiver_journey_avg_pct' => $journeyAvgPct,
            'avg_support_response_hours' => $responseTimes->isEmpty() ? null : round($responseTimes->avg(), 1),
            'passport_confirmed_pct' => $this->percentage($confirmedPassports->count(), $cases->count()),
        ];
    }

    /**
     * Casos que necesitan atención: un cuidador autorizado que nunca ha entrado, o un
     * caso sin ninguna actividad de portal reciente. No es una tabla ni un job
     * programado — se calcula al vuelo sobre los casos ya cargados.
     *
     * @param  Collection<int, PicsCase>  $cases
     * @return array<int, array{case: PicsCase, reason: string, days: int}>
     */
    public function inactivityAlerts(Collection $cases): array
    {
        $alerts = [];

        foreach ($cases as $case) {
            if (in_array($case->status, [CaseStatus::Completed, CaseStatus::Cancelled], true)) {
                continue;
            }

            $snapshot = $this->caseSnapshot($case);

            if ($snapshot['caregiver_authorized']
                && $snapshot['caregiver_last_login_at'] === null
                && $snapshot['caregiver_authorized_at']
                && Carbon::parse($snapshot['caregiver_authorized_at'])->lt(now()->subDays(3))) {
                $alerts[] = [
                    'case' => $case,
                    'reason' => 'cuidador_sin_ingresar',
                    'days' => (int) Carbon::parse($snapshot['caregiver_authorized_at'])->diffInDays(now()),
                ];
            }

            $lastActivity = $snapshot['last_portal_activity_at'];
            if ($lastActivity === null || Carbon::parse($lastActivity)->lt(now()->subDays(15))) {
                $alerts[] = [
                    'case' => $case,
                    'reason' => 'sin_actividad',
                    'days' => $lastActivity === null ? null : (int) Carbon::parse($lastActivity)->diffInDays(now()),
                ];
            }
        }

        return $alerts;
    }

    public const ALERT_LABELS = [
        'cuidador_sin_ingresar' => 'El cuidador autorizado nunca ha ingresado al portal',
        'sin_actividad' => 'Sin actividad reciente en el portal',
    ];

    /**
     * Actividad originada en el portal (diario, reportes de metas, autorreportes de
     * bienestar, solicitudes de ayuda), agrupada por semana, para ver la tendencia
     * de las últimas $weeks semanas. Opera sobre las colecciones ya cargadas.
     *
     * @param  Collection<int, PicsCase>  $cases
     * @return array<int, array{label: string, value: int}>
     */
    public function weeklyActivityTrend(Collection $cases, int $weeks = 8): array
    {
        $events = collect();

        foreach ($cases as $case) {
            $events = $events
                ->merge($case->diaryEntries->pluck('created_at'))
                ->merge($case->recoveryGoals->flatMap->progressReports->pluck('reported_at'))
                ->merge($case->followups->whereNotNull('submitted_by_type')->pluck('followed_up_at'))
                ->merge($case->supportRequests->pluck('created_at'));
        }

        $events = $events->filter()->map(fn ($d) => CarbonImmutable::parse($d));

        $rows = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->startOfWeek()->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();

            $rows[] = [
                'label' => $weekStart->format('d/m'),
                'value' => $events->filter(fn (CarbonImmutable $d) => $d->between($weekStart, $weekEnd))->count(),
            ];
        }

        return $rows;
    }

    private function percentage(int $numerator, int $denominator): ?float
    {
        return $denominator === 0 ? null : round(($numerator / $denominator) * 100, 1);
    }
}
