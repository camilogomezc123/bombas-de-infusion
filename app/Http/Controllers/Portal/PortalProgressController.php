<?php

namespace App\Http\Controllers\Portal;

use App\Models\Caregiver;
use App\Models\HomeMonitoringReading;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Convierte en gráficas de tendencia lo que hoy solo se ve como listas: las
 * lecturas de monitoreo en casa y los autorreportes de bienestar del actor. Solo
 * datos ya estructurados y validados (los mismos puntajes que ya usa el equipo en
 * PicsFollowup) — nada calculado ni interpretado de nuevo aquí.
 */
class PortalProgressController
{
    public function show(): View
    {
        $case = PortalHomeController::currentCase();

        if (! $case) {
            return view('portal.progress', ['case' => null]);
        }

        $isCaregiver = Auth::guard('caregiver')->user() instanceof Caregiver;

        $case->loadMissing(['homeMonitoringReadings', 'followups']);

        $monitoringCharts = $case->homeMonitoringReadings
            ->sortBy('measured_at')
            ->groupBy('reading_type')
            ->filter(fn ($readings) => $readings->count() >= 2)
            ->map(fn ($readings) => [
                'label' => HomeMonitoringReading::READING_TYPES[$readings->first()->reading_type] ?? $readings->first()->reading_type,
                'unit' => $readings->first()->unit,
                'labels' => $readings->pluck('measured_at')->map(fn ($d) => $d->format('d/m')),
                'values' => $readings->pluck('value'),
            ])
            ->values();

        $wellbeingFollowups = $case->followups
            ->where('respondent_type', $isCaregiver ? 'familia' : 'paciente')
            ->whereNotNull('followed_up_at')
            ->sortBy('followed_up_at');

        $wellbeingChart = null;

        if ($wellbeingFollowups->count() >= 2) {
            $wellbeingChart = $isCaregiver
                ? [
                    'labels' => $wellbeingFollowups->pluck('followed_up_at')->map(fn ($d) => $d->format('d/m')),
                    'series' => [
                        ['label' => 'Carga del cuidador (PICS-F)', 'values' => $wellbeingFollowups->pluck('picsf_distress')],
                    ],
                ]
                : [
                    'labels' => $wellbeingFollowups->pluck('followed_up_at')->map(fn ($d) => $d->format('d/m')),
                    'series' => [
                        ['label' => 'Ansiedad (HADS-A)', 'values' => $wellbeingFollowups->pluck('hads_ansiedad')],
                        ['label' => 'Ánimo (PHQ-9)', 'values' => $wellbeingFollowups->pluck('phq9_score')],
                    ],
                ];
        }

        return view('portal.progress', [
            'case' => $case,
            'monitoringCharts' => $monitoringCharts,
            'wellbeingChart' => $wellbeingChart,
        ]);
    }
}
