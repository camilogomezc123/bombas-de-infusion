<?php

namespace App\Http\Controllers\Portal;

use App\Models\Caregiver;
use App\Models\MedicationReconciliationItem;
use App\Models\Patient;
use App\Models\PicsAgendaItem;
use App\Models\PicsCase;
use App\Models\PicsReferral;
use App\Support\Posuci\CaseAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Fuente de eventos para el calendario del portal (FullCalendar la consulta
 * directamente por JS al navegar de mes). Es una vista de lectura combinada —igual
 * espíritu que PicsAgendaService::upcoming(), pero para el paciente/familia— más los
 * recordatorios personales, que solo ve quien los creó.
 */
class PortalCalendarController
{
    private const TYPE_COLORS = [
        'cita' => '#0ea5e9',
        'terapia' => '#7c3aed',
        'tarea' => '#64748b',
        'recordatorio' => '#facc15',
    ];

    private const REFERRAL_COLOR = '#0e7490';

    private const MEDICATION_COLOR = '#f97316';

    private const PERSONAL_REMINDER_COLOR = '#ec4899';

    public function events(Request $request): JsonResponse
    {
        $case = PortalHomeController::currentCase();
        $patient = Auth::guard('patient')->user();
        $caregiver = Auth::guard('caregiver')->user();

        $actor = null;
        if ($patient instanceof Patient && $case && CaseAccess::patientCanAccess($patient, $case)) {
            $actor = $patient;
        } elseif ($caregiver instanceof Caregiver && $case && CaseAccess::caregiverCanAccess($caregiver, $case)) {
            $actor = $caregiver;
        }

        abort_unless($case && $actor, 403);

        $start = Carbon::parse($request->query('start', now()->startOfMonth()->toDateString()));
        $end = Carbon::parse($request->query('end', now()->endOfMonth()->toDateString()));

        $events = collect()
            ->merge($this->agendaItemEvents($case, $start, $end))
            ->merge($this->referralEvents($case, $start, $end))
            ->merge($this->medicationEvents($case, $start, $end))
            ->merge($this->personalReminderEvents($case, $actor, $start, $end));

        return response()->json($events->values());
    }

    private function agendaItemEvents(PicsCase $case, Carbon $start, Carbon $end): array
    {
        return PicsAgendaItem::query()
            ->where('pics_case_id', $case->id)
            ->where('status', 'pendiente')
            ->whereBetween('scheduled_at', [$start, $end])
            ->get()
            ->map(function (PicsAgendaItem $item): array {
                $responseIcon = match ($item->patient_response) {
                    'confirmada' => '✅ ',
                    'no_asistira' => '❌ ',
                    default => '',
                };

                return [
                    'title' => $responseIcon.(PicsAgendaItem::TYPES[$item->type] ?? $item->type).': '.$item->title,
                    'start' => $item->scheduled_at->toIso8601String(),
                    'color' => self::TYPE_COLORS[$item->type] ?? '#64748b',
                    'extendedProps' => [
                        'kind' => 'agenda_item',
                        'type' => $item->type,
                        'notes' => $item->notes,
                        'id' => $item->id,
                        'respondable' => $item->isRespondable() && $item->patient_response === null,
                        'patientResponse' => $item->patient_response,
                    ],
                ];
            })
            ->all();
    }

    private function referralEvents(PicsCase $case, Carbon $start, Carbon $end): array
    {
        return PicsReferral::query()
            ->where('pics_case_id', $case->id)
            ->whereIn('status', ['open', 'scheduled'])
            ->get()
            ->filter(function (PicsReferral $referral) use ($start, $end): bool {
                $when = $referral->scheduled_at ?? $referral->referred_at;

                return $when && $when->between($start, $end);
            })
            ->map(fn (PicsReferral $referral): array => [
                'title' => 'Remisión: '.$referral->specialty,
                'start' => ($referral->scheduled_at ?? $referral->referred_at)->toIso8601String(),
                'color' => self::REFERRAL_COLOR,
                'extendedProps' => ['kind' => 'referral', 'notes' => $referral->notes],
            ])
            ->values()
            ->all();
    }

    /**
     * Un medicamento solo genera eventos si el staff diligenció horarios concretos
     * (schedule_times) — nunca se adivinan horas a partir de "frequency" en texto
     * libre, eso sería un riesgo real de seguridad del paciente.
     */
    private function medicationEvents(PicsCase $case, Carbon $start, Carbon $end): array
    {
        $items = MedicationReconciliationItem::query()
            ->whereHas('reconciliation', fn ($query) => $query->where('pics_case_id', $case->id))
            ->where('status', '!=', 'suspendida')
            ->whereNotNull('schedule_times')
            ->get();

        $events = [];

        foreach ($items as $item) {
            $times = $item->schedule_times ?: [];

            for ($day = $start->copy()->startOfDay(); $day->lte($end); $day->addDay()) {
                foreach ($times as $time) {
                    if (! preg_match('/^\d{2}:\d{2}/', (string) $time)) {
                        continue;
                    }

                    $events[] = [
                        'title' => '💊 '.$item->medication_name,
                        'start' => $day->copy()->setTimeFromTimeString($time)->toIso8601String(),
                        'color' => self::MEDICATION_COLOR,
                        'extendedProps' => ['kind' => 'medication', 'notes' => $item->dose],
                    ];
                }
            }
        }

        return $events;
    }

    private function personalReminderEvents(PicsCase $case, Patient|Caregiver $actor, Carbon $start, Carbon $end): array
    {
        return $case->personalReminders()
            ->where('created_by_type', $actor::class)
            ->where('created_by_id', $actor->id)
            ->whereBetween('remind_at', [$start, $end])
            ->get()
            ->map(fn ($reminder): array => [
                'title' => '📌 '.$reminder->title,
                'start' => $reminder->remind_at->toIso8601String(),
                'color' => self::PERSONAL_REMINDER_COLOR,
                'editable' => true,
                'extendedProps' => ['kind' => 'personal_reminder', 'notes' => $reminder->notes, 'id' => $reminder->id],
            ])
            ->all();
    }
}
