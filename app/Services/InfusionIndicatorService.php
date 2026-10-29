<?php

namespace App\Services;

use App\Models\InfusionClinicalAlert;
use App\Models\InfusionEvent;
use App\Models\VisScoreSnapshot;

/**
 * Indicadores en vivo del módulo de Monitoreo de Bombas, consumidos por
 * IndicatorDefinition::liveResult() (programa BOMBAS). A diferencia de los demás
 * programas (que reportan por año), estos son indicadores de estado actual — no hay
 * todavía suficiente histórico acumulado para un corte anual con sentido clínico.
 */
class InfusionIndicatorService
{
    public function summary(): array
    {
        $totalEvents = InfusionEvent::query()->count();
        $hardOverrides = InfusionEvent::query()
            ->where('alert_level', 'like', '%Duro%')
            ->whereIn('next_action', ['Anulaciones', 'Retrocesos'])
            ->count();
        $unresolvedEvents = InfusionEvent::query()->unresolved()->count();

        $latestVisPerStay = VisScoreSnapshot::query()
            ->orderByDesc('computed_at')
            ->get()
            ->unique('icu_stay_id');

        return [
            'hard_limit_override_rate_pct' => $totalEvents ? round($hardOverrides / $totalEvents * 100, 1) : null,
            'unresolved_events_pct' => $totalEvents ? round($unresolvedEvents / $totalEvents * 100, 1) : null,
            'avg_vis_score' => $latestVisPerStay->isNotEmpty() ? round((float) $latestVisPerStay->avg('vis_score'), 2) : null,
            'open_pis_alerts' => InfusionClinicalAlert::query()->where('type', 'pis_risk')->where('status', '!=', 'resolved')->count(),
            'open_critical_alerts' => InfusionClinicalAlert::query()->where('severity', 'critical')->where('status', 'open')->count(),
            'total_events' => $totalEvents,
            'library_compliance_pct' => $totalEvents
                ? round(InfusionEvent::query()->where('used_drug_library', true)->count() / $totalEvents * 100, 1)
                : null,
            'open_antibiotic_interruptions' => InfusionClinicalAlert::query()
                ->where('type', 'antibiotic_interruption')->where('status', '!=', 'resolved')->count(),
            'open_pain_assessment_gaps' => InfusionClinicalAlert::query()
                ->where('type', 'pain_assessment_overdue')->where('status', '!=', 'resolved')->count(),
            'open_therapeutic_duplications' => InfusionClinicalAlert::query()
                ->where('type', 'therapeutic_duplication')->where('status', '!=', 'resolved')->count(),
            'co_sign_compliance_pct' => $this->coSignCompliance(),
        ];
    }

    /**
     * % de eventos de fármacos de alto riesgo (vasoactivo/opioide/sedación) con doble
     * verificación registrada — la bomba reporta quién anuló un límite, pero no quién
     * verificó la programación normal; eso lo registra enfermería manualmente aquí.
     */
    private function coSignCompliance(): ?float
    {
        $highRisk = InfusionEvent::query()
            ->whereHas('drugInfusionProfile', fn ($q) => $q->whereIn('category', InfusionEvent::HIGH_RISK_CATEGORIES))
            ->count();

        if (! $highRisk) {
            return null;
        }

        $coSigned = InfusionEvent::query()
            ->whereHas('drugInfusionProfile', fn ($q) => $q->whereIn('category', InfusionEvent::HIGH_RISK_CATEGORIES))
            ->whereNotNull('co_signed_at')
            ->count();

        return round($coSigned / $highRisk * 100, 1);
    }
}
