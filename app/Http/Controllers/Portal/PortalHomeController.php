<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PicsAgendaItem;
use App\Models\PicsCase;
use App\Support\Posuci\CaseAccess;
use App\Support\Posuci\DailyTip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalHomeController extends Controller
{
    /**
     * Puntos "de participación" por tipo de actividad — es una capa de motivación de
     * interfaz, no un puntaje clínico: nunca se guarda en base de datos, se calcula al
     * vuelo a partir de lo que el actor actual ya reportó.
     */
    private const POINTS = [
        'diary_entry' => 10,
        'goal_progress_report' => 15,
        'wellbeing_self_report' => 15,
        'passport_reported' => 25,
        'home_monitoring_reading' => 5,
        'education_viewed' => 10,
        'discharge_readiness_reviewed' => 10,
        'caregiver_journey_step' => 15,
        'support_request_created' => 5,
    ];

    private const POINTS_PER_LEVEL = 60;

    private const LEVEL_TITLES = [
        1 => 'Explorador/a',
        2 => 'Aventurero/a',
        3 => 'Guerrero/a de la recuperación',
        4 => 'Campeón/a',
        5 => 'Héroe/ína de POSUCI',
        6 => 'Leyenda de POSUCI',
    ];

    public function index(): View
    {
        $case = $this->currentCase();

        if (! $case) {
            return view('portal.home', ['case' => null]);
        }

        $patient = Auth::guard('patient')->user();
        $caregiver = Auth::guard('caregiver')->user();
        $actor = $patient instanceof Patient ? $patient : $caregiver;
        $canAccessJourney = $caregiver instanceof Caregiver && CaseAccess::caregiverCanAccessJourney($caregiver, $case);

        $case->loadMissing([
            'recoveryGoals.progressReports', 'educationAssignments.resource', 'dischargeReadinessCheck.items',
            'supportRequests', 'caregiverJourneySteps', 'diaryEntries', 'homeMonitoringReadings',
            'recoveryPassport', 'followups', 'agendaItems', 'medicationReconciliation.items', 'personalReminders',
        ]);
        $actor?->loadMissing('pushSubscriptions');

        $pendingGoals = $case->recoveryGoals->where('status', 'active')->count();

        $unreadEducation = $case->educationAssignments
            ->filter(fn ($assignment) => $assignment->resource?->is_active && $assignment->viewed_at === null)
            ->count();

        $pendingReadinessItems = $case->dischargeReadinessCheck?->items
            ->filter(fn ($item) => $item->reviewed_at === null)
            ->count() ?? 0;

        $openSupportRequests = $case->supportRequests->whereNull('response_text')->count();

        $pendingJourneySteps = $canAccessJourney
            ? $case->caregiverJourneySteps->whereNull('reported_at')->count()
            : null;

        $gamification = $actor ? $this->computeGamification($case, $actor, $canAccessJourney) : null;
        $ritual = $actor ? $this->computeTodayRitual($case, $actor) : null;
        $firstSteps = $actor ? $this->computeFirstSteps($case, $actor) : null;
        $nextAppointment = $case->agendaItems
            ->where('status', 'pendiente')
            ->whereIn('type', PicsAgendaItem::RESPONDABLE_TYPES)
            ->filter(fn ($item) => $item->scheduled_at && $item->scheduled_at->gte(now()))
            ->sortBy('scheduled_at')
            ->first();

        $missions = collect([
            ['title' => 'Mi calendario', 'description' => 'Citas, terapias, medicamentos y tus recordatorios.', 'url' => route('portal.calendar'), 'icon' => '📅', 'color' => 'linear-gradient(135deg,#0ea5e9,#7c3aed)'],
            ['title' => 'Resumen para tu cita', 'description' => 'Una página lista para imprimir o mostrar en tu próxima consulta.', 'url' => route('portal.summary'), 'icon' => '🖨️', 'color' => 'linear-gradient(135deg,#14b8a6,#0e7490)'],
            ['title' => 'Mi progreso', 'description' => 'Gráficas de tu monitoreo y bienestar en el tiempo.', 'url' => route('portal.progress'), 'icon' => '📈', 'color' => 'linear-gradient(135deg,#7c3aed,#0ea5e9)'],
            ['title' => 'Antes y ahora', 'description' => 'Tu pasaporte de recuperación.', 'url' => route('portal.passport'), 'icon' => '📖', 'color' => 'linear-gradient(135deg,#0ea5e9,#0e7490)'],
            ['title' => 'Mi diario', 'description' => 'Lo que tu familia — y tú — han escrito.', 'url' => route('portal.diary'), 'icon' => '✍️', 'color' => 'linear-gradient(135deg,#7c3aed,#5b21b6)'],
            ['title' => 'Mis metas', 'description' => 'Tus metas de recuperación y tu avance.', 'url' => route('portal.goals'), 'icon' => '🎯', 'color' => 'linear-gradient(135deg,#f97316,#ea580c)', 'pending' => $pendingGoals],
            ['title' => 'Cómo me siento', 'description' => 'Autorreporte de bienestar.', 'url' => route('portal.wellbeing'), 'icon' => '💙', 'color' => 'linear-gradient(135deg,#ec4899,#db2777)'],
            ['title' => 'Necesito ayuda', 'description' => 'Reporta una dificultad o una duda.', 'url' => route('portal.support'), 'icon' => '🆘', 'color' => 'linear-gradient(135deg,#ef4444,#b91c1c)', 'pending' => $openSupportRequests],
            ['title' => 'Medicamentos', 'description' => 'Los medicamentos conciliados por tu equipo.', 'url' => route('portal.medications'), 'icon' => '💊', 'color' => 'linear-gradient(135deg,#0ea5e9,#2563eb)'],
            ['title' => 'Monitoreo en casa', 'description' => 'Registra tus lecturas (saturación, presión, etc.).', 'url' => route('portal.home-monitoring'), 'icon' => '🩺', 'color' => 'linear-gradient(135deg,#22c55e,#15803d)'],
            ['title' => 'Preparación para el alta', 'description' => 'Temas clave antes de salir.', 'url' => route('portal.discharge-readiness'), 'icon' => '🎓', 'color' => 'linear-gradient(135deg,#facc15,#ca8a04)', 'pending' => $pendingReadinessItems],
            ['title' => 'Educación', 'description' => 'Contenido elegido para tu recuperación.', 'url' => route('portal.education'), 'icon' => '📚', 'color' => 'linear-gradient(135deg,#6366f1,#4338ca)', 'pending' => $unreadEducation],
        ]);

        if ($canAccessJourney) {
            $missions->push(['title' => 'Mi ruta como cuidador', 'description' => 'Pasos para prepararte a acompañar.', 'url' => route('portal.caregiver-journey'), 'icon' => '🤝', 'color' => 'linear-gradient(135deg,#14b8a6,#0f766e)', 'pending' => $pendingJourneySteps]);
        }

        return view('portal.home', [
            'case' => $case,
            'showTour' => $actor !== null && ! $actor->has_seen_portal_tour,
            'pendingGoals' => $pendingGoals,
            'unreadEducation' => $unreadEducation,
            'pendingReadinessItems' => $pendingReadinessItems,
            'openSupportRequests' => $openSupportRequests,
            'pendingJourneySteps' => $pendingJourneySteps,
            'missions' => $missions,
            'gamification' => $gamification,
            'ritual' => $ritual,
            'firstSteps' => $firstSteps,
            'dailyTip' => DailyTip::forDate(),
            'actorFirstName' => $this->firstName($actor),
            'nextAppointment' => $nextAppointment,
        ]);
    }

    /**
     * El "ritual del día": qué ya hiciste hoy (de lo que ya reportas en el portal) y qué
     * tienes agendado hoy (citas, terapias, medicamentos con horario, tus recordatorios),
     * agrupado en mañana/tarde/noche solo como sugerencia de cuándo hacerlo — el sistema
     * no exige que se haga a esa hora exacta, es puramente para formar el hábito diario.
     *
     * @return array<string, array{checklist: array<int, array{icon: string, title: string, url: string, done: bool}>, agenda: array<int, array{time: string, icon: string, label: string}>}>
     */
    private function computeTodayRitual(PicsCase $case, Patient|Caregiver $actor): array
    {
        $actorType = $actor::class;
        $actorId = $actor->id;
        $today = today();

        $didToday = fn (Collection $items, string $dateField): bool => $items
            ->filter(fn ($item) => $item->{$dateField} && $item->{$dateField}->isSameDay($today))
            ->isNotEmpty();

        $didWellbeingToday = $didToday(
            $case->followups->where('submitted_by_type', $actorType)->where('submitted_by_id', $actorId),
            'followed_up_at',
        );
        $didMonitoringToday = $didToday(
            $case->homeMonitoringReadings->where('recorded_by_type', $actorType)->where('recorded_by_id', $actorId),
            'measured_at',
        );
        $didDiaryToday = $didToday(
            $case->diaryEntries->where('authorable_type', $actorType)->where('authorable_id', $actorId),
            'created_at',
        );
        $didGoalReportToday = $didToday(
            $case->recoveryGoals->flatMap->progressReports->where('reporter_type', $actorType)->where('reporter_id', $actorId),
            'reported_at',
        );

        $agenda = $this->todayAgendaEntries($case, $actor, $today);
        $byWindow = fn (int $from, int $to) => $agenda
            ->filter(fn ($entry) => $entry['time']->hour >= $from && $entry['time']->hour < $to)
            ->map(fn ($entry) => ['time' => $entry['time']->format('H:i'), 'icon' => $entry['icon'], 'label' => $entry['label']])
            ->values()->all();

        return [
            'morning' => [
                'label' => 'Mañana',
                'icon' => '🌅',
                'checklist' => [
                    ['icon' => '💙', 'title' => '¿Cómo amaneciste?', 'url' => route('portal.wellbeing'), 'done' => $didWellbeingToday],
                ],
                'agenda' => $byWindow(0, 12),
            ],
            'afternoon' => [
                'label' => 'Tarde',
                'icon' => '☀️',
                'checklist' => [
                    ['icon' => '🩺', 'title' => 'Monitoreo en casa', 'url' => route('portal.home-monitoring'), 'done' => $didMonitoringToday],
                    ['icon' => '🎯', 'title' => 'Avance de tus metas', 'url' => route('portal.goals'), 'done' => $didGoalReportToday],
                ],
                'agenda' => $byWindow(12, 18),
            ],
            'night' => [
                'label' => 'Noche',
                'icon' => '🌙',
                'checklist' => [
                    ['icon' => '✍️', 'title' => 'Escribe en tu diario', 'url' => route('portal.diary'), 'done' => $didDiaryToday],
                ],
                'agenda' => $byWindow(18, 24),
            ],
        ];
    }

    /**
     * @return Collection<int, array{time: Carbon, icon: string, label: string}>
     */
    private function todayAgendaEntries(PicsCase $case, Patient|Caregiver $actor, Carbon $today): Collection
    {
        $agendaItems = $case->agendaItems
            ->where('status', 'pendiente')
            ->filter(fn ($item) => $item->scheduled_at && $item->scheduled_at->isSameDay($today))
            ->map(fn ($item) => [
                'time' => $item->scheduled_at,
                'icon' => match ($item->type) {
                    'cita' => '🩺',
                    'terapia' => '🧑‍⚕️',
                    'tarea' => '📋',
                    default => '🗒️',
                },
                'label' => (PicsAgendaItem::TYPES[$item->type] ?? $item->type).': '.$item->title,
            ]);

        $medications = collect();
        foreach ($case->medicationReconciliation?->items ?? [] as $item) {
            if ($item->status === 'suspendida' || empty($item->schedule_times)) {
                continue;
            }

            foreach ($item->schedule_times as $time) {
                if (! preg_match('/^\d{2}:\d{2}/', (string) $time)) {
                    continue;
                }

                $medications->push([
                    'time' => $today->copy()->setTimeFromTimeString($time),
                    'icon' => '💊',
                    'label' => $item->medication_name,
                ]);
            }
        }

        $reminders = $case->personalReminders
            ->where('created_by_type', $actor::class)->where('created_by_id', $actor->id)
            ->filter(fn ($reminder) => $reminder->remind_at && $reminder->remind_at->isSameDay($today))
            ->map(fn ($reminder) => ['time' => $reminder->remind_at, 'icon' => '📌', 'label' => $reminder->title]);

        return collect()->merge($agendaItems)->merge($medications)->merge($reminders)->sortBy('time')->values();
    }

    /**
     * Checklist persistente para quien recién entra — a diferencia del tour (que se ve
     * una sola vez), esta se sigue mostrando hasta completar todos los pasos. Se oculta
     * sola cuando ya no queda nada pendiente.
     *
     * @return array<int, array{icon: string, title: string, url: ?string, done: bool}>
     */
    private function computeFirstSteps(PicsCase $case, Patient|Caregiver $actor): array
    {
        $actorType = $actor::class;
        $actorId = $actor->id;

        $passportReportedByActor = $case->recoveryPassport
            && $case->recoveryPassport->reported_by_type === $actorType
            && $case->recoveryPassport->reported_by_id === $actorId;

        $diaryCount = $case->diaryEntries->where('authorable_type', $actorType)->where('authorable_id', $actorId)->count();
        $reminderCount = $case->personalReminders->where('created_by_type', $actorType)->where('created_by_id', $actorId)->count();
        $pushSubscriptionCount = $actor->pushSubscriptions->count();

        return [
            ['icon' => '📖', 'title' => 'Completa tu "Antes y ahora"', 'url' => route('portal.passport'), 'done' => $passportReportedByActor],
            ['icon' => '✍️', 'title' => 'Escribe tu primera entrada del diario', 'url' => route('portal.diary'), 'done' => $diaryCount >= 1],
            ['icon' => '📌', 'title' => 'Agrega tu primer recordatorio', 'url' => route('portal.calendar'), 'done' => $reminderCount >= 1],
            ['icon' => '🔔', 'title' => 'Activa las notificaciones (botón arriba)', 'url' => null, 'done' => $pushSubscriptionCount >= 1],
        ];
    }

    /**
     * @return array{points: int, level: int, levelTitle: string, xpIntoLevel: int, xpForNextLevel: int, xpProgressPct: float, badges: array<int, array{icon: string, label: string, unlocked: bool}>, streakDays: int}
     */
    private function computeGamification(PicsCase $case, Patient|Caregiver $actor, bool $canAccessJourney): array
    {
        $actorType = $actor::class;
        $actorId = $actor->id;

        $diaryCount = $case->diaryEntries->where('authorable_type', $actorType)->where('authorable_id', $actorId)->count();
        $goalReportsCount = $case->recoveryGoals->flatMap->progressReports
            ->where('reporter_type', $actorType)->where('reporter_id', $actorId)->count();
        $wellbeingCount = $case->followups->where('submitted_by_type', $actorType)->where('submitted_by_id', $actorId)->count();
        $passportReportedByActor = $case->recoveryPassport
            && $case->recoveryPassport->reported_by_type === $actorType
            && $case->recoveryPassport->reported_by_id === $actorId;
        $monitoringCount = $case->homeMonitoringReadings->where('recorded_by_type', $actorType)->where('recorded_by_id', $actorId)->count();
        $educationViewedCount = $case->educationAssignments->where('viewed_by_type', $actorType)->where('viewed_by_id', $actorId)->count();
        $readinessReviewedCount = $case->dischargeReadinessCheck?->items
            ->where('reviewed_by_type', $actorType)->where('reviewed_by_id', $actorId)->count() ?? 0;
        $journeyStepsCount = $case->caregiverJourneySteps->where('reported_by_type', $actorType)->where('reported_by_id', $actorId)->count();
        $supportRequestsCount = $case->supportRequests->where('created_by_type', $actorType)->where('created_by_id', $actorId)->count();

        $activityDates = collect([
            $case->diaryEntries->where('authorable_type', $actorType)->where('authorable_id', $actorId)->pluck('created_at'),
            $case->recoveryGoals->flatMap->progressReports->where('reporter_type', $actorType)->where('reporter_id', $actorId)->pluck('reported_at'),
            $case->followups->where('submitted_by_type', $actorType)->where('submitted_by_id', $actorId)->pluck('followed_up_at'),
            $case->homeMonitoringReadings->where('recorded_by_type', $actorType)->where('recorded_by_id', $actorId)->pluck('measured_at'),
            $case->educationAssignments->where('viewed_by_type', $actorType)->where('viewed_by_id', $actorId)->pluck('viewed_at'),
            ($case->dischargeReadinessCheck?->items ?? collect())->where('reviewed_by_type', $actorType)->where('reviewed_by_id', $actorId)->pluck('reviewed_at'),
            $case->caregiverJourneySteps->where('reported_by_type', $actorType)->where('reported_by_id', $actorId)->pluck('reported_at'),
            $case->supportRequests->where('created_by_type', $actorType)->where('created_by_id', $actorId)->pluck('created_at'),
            $passportReportedByActor ? collect([$case->recoveryPassport->reported_at]) : collect(),
        ])->flatten(1);

        $points = $diaryCount * self::POINTS['diary_entry']
            + $goalReportsCount * self::POINTS['goal_progress_report']
            + $wellbeingCount * self::POINTS['wellbeing_self_report']
            + ($passportReportedByActor ? self::POINTS['passport_reported'] : 0)
            + $monitoringCount * self::POINTS['home_monitoring_reading']
            + $educationViewedCount * self::POINTS['education_viewed']
            + $readinessReviewedCount * self::POINTS['discharge_readiness_reviewed']
            + $journeyStepsCount * self::POINTS['caregiver_journey_step']
            + $supportRequestsCount * self::POINTS['support_request_created'];

        $level = intdiv($points, self::POINTS_PER_LEVEL) + 1;
        $xpIntoLevel = $points % self::POINTS_PER_LEVEL;

        $badges = [
            ['icon' => '🖊️', 'label' => 'Cronista', 'unlocked' => $diaryCount >= 1],
            ['icon' => '🎯', 'label' => 'En marcha', 'unlocked' => $goalReportsCount >= 1],
            ['icon' => '💙', 'label' => 'Conectado/a', 'unlocked' => $wellbeingCount >= 1],
            ['icon' => '📋', 'label' => 'Organizado/a', 'unlocked' => $passportReportedByActor],
            ['icon' => '📚', 'label' => 'Estudioso/a', 'unlocked' => $educationViewedCount >= 1],
            ['icon' => '🩺', 'label' => 'Constante', 'unlocked' => $monitoringCount >= 3],
        ];

        if ($canAccessJourney) {
            $badges[] = ['icon' => '🤝', 'label' => 'Acompañante', 'unlocked' => $journeyStepsCount >= 1];
        }

        return [
            'points' => $points,
            'level' => $level,
            'levelTitle' => self::LEVEL_TITLES[min($level, 6)],
            'xpIntoLevel' => $xpIntoLevel,
            'xpForNextLevel' => self::POINTS_PER_LEVEL,
            'xpProgressPct' => round(($xpIntoLevel / self::POINTS_PER_LEVEL) * 100, 1),
            'badges' => $badges,
            'streakDays' => $this->computeStreak($activityDates),
        ];
    }

    /**
     * Días consecutivos (hasta hoy, o hasta ayer si hoy todavía no hay actividad) con
     * al menos una actividad del actor. Igual que los puntos: puramente de interfaz,
     * no se guarda en base de datos.
     */
    private function computeStreak(Collection $dates): int
    {
        $days = $dates->filter()->map(fn ($d) => Carbon::parse($d)->toDateString())->unique();

        if ($days->isEmpty()) {
            return 0;
        }

        $cursor = today();

        if (! $days->contains($cursor->toDateString())) {
            $cursor = $cursor->copy()->subDay();

            if (! $days->contains($cursor->toDateString())) {
                return 0;
            }
        }

        $streak = 0;
        while ($days->contains($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->copy()->subDay();
        }

        return $streak;
    }

    private function firstName(Patient|Caregiver|null $actor): string
    {
        if ($actor instanceof Patient) {
            return trim(explode(' ', $actor->full_name)[0] ?? $actor->full_name);
        }

        if ($actor instanceof Caregiver) {
            return trim(explode(' ', $actor->name)[0] ?? $actor->name);
        }

        return '';
    }

    public function dismissTour(): RedirectResponse
    {
        $actor = Auth::guard('patient')->user() ?? Auth::guard('caregiver')->user();
        $actor?->update(['has_seen_portal_tour' => true]);

        return redirect()->route('portal.home');
    }

    /**
     * "Modo fácil": letra más grande, más contraste, botones más grandes — pensado para
     * que cualquier edad pueda usar el portal cómodamente. Se guarda en la cuenta del
     * actor (no en el navegador) para que se mantenga activo entre dispositivos y visitas.
     */
    public function toggleEasyMode(): RedirectResponse
    {
        $actor = Auth::guard('patient')->user() ?? Auth::guard('caregiver')->user();
        $actor?->update(['portal_easy_mode' => ! $actor->portal_easy_mode]);

        return redirect()->back();
    }

    public static function currentCase(): ?PicsCase
    {
        if ($patient = Auth::guard('patient')->user()) {
            /** @var Patient $patient */
            return CaseAccess::currentCaseForPatient($patient);
        }

        if ($caregiver = Auth::guard('caregiver')->user()) {
            /** @var Caregiver $caregiver */
            return CaseAccess::currentCaseForCaregiver($caregiver);
        }

        return null;
    }
}
