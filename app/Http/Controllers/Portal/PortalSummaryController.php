<?php

namespace App\Http\Controllers\Portal;

use Illuminate\View\View;

/**
 * Resumen de una página para llevar (o mostrar en el celular) a la próxima cita
 * médica: solo datos ya estructurados y validados por el sistema (nada de texto
 * libre del diario, nada inventado) — los mismos semáforos clínicos que ya usa el
 * equipo en PicsFollowup, la lista de medicamentos conciliada, metas activas y las
 * últimas lecturas de monitoreo en casa.
 */
class PortalSummaryController
{
    public function show(): View
    {
        $case = PortalHomeController::currentCase();

        if (! $case) {
            return view('portal.summary', ['case' => null]);
        }

        $case->loadMissing([
            'medicationReconciliation.items', 'recoveryGoals.progressReports',
            'homeMonitoringReadings', 'followups', 'supportRequests', 'agendaItems',
        ]);

        $medications = ($case->medicationReconciliation?->items ?? collect())
            ->where('status', '!=', 'suspendida')
            ->sortBy('sort_order')
            ->values();

        $activeGoals = $case->recoveryGoals->where('status', 'active')
            ->map(fn ($goal) => [
                'goal' => $goal,
                'latestReport' => $goal->progressReports->sortByDesc('reported_at')->first(),
            ])
            ->values();

        $recentReadings = $case->homeMonitoringReadings->sortByDesc('measured_at')->take(8)->values();

        $latestWellbeing = $case->followups->sortByDesc('followed_up_at')->first();

        $openSupportRequests = $case->supportRequests->whereNull('response_text')->values();

        $upcomingAgenda = $case->agendaItems
            ->where('status', 'pendiente')
            ->filter(fn ($item) => $item->scheduled_at && $item->scheduled_at->isFuture())
            ->sortBy('scheduled_at')
            ->take(5)
            ->values();

        return view('portal.summary', [
            'case' => $case,
            'medications' => $medications,
            'activeGoals' => $activeGoals,
            'recentReadings' => $recentReadings,
            'latestWellbeing' => $latestWellbeing,
            'openSupportRequests' => $openSupportRequests,
            'upcomingAgenda' => $upcomingAgenda,
            'printedAt' => now(),
        ]);
    }
}
