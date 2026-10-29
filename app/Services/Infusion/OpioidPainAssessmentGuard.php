<?php

namespace App\Services\Infusion;

use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;

/**
 * Enlaza el dato estructurado de bomba (BOMBAS) con el componente "A" (Assess/treat
 * pain) del bundle ABCDEF de ICU Liberation: si una estancia tiene una infusión de
 * opioide activa (categoría analgesia) pero ICU Liberation no registró una evaluación
 * de dolor (IcuPainAssessment) dentro de la ventana de vigilancia, es un vacío de
 * documentación del bundle que BOMBAS puede detectar antes que un reporte retrospectivo
 * — ICU Liberation hoy solo registra analgésicos como texto libre, sin cruzarlo contra
 * la infusión real de la bomba. No evalúa si el dolor está bien controlado (eso requiere
 * la escala CPOT/NRS que sí registra ICU Liberation), solo si se evaluó.
 */
class OpioidPainAssessmentGuard
{
    private const DOCUMENTATION_WINDOW_HOURS = 8;

    public function evaluate(IcuStay $icuStay): void
    {
        if ($icuStay->status !== 'active') {
            return;
        }

        $latestOpioidEvent = $icuStay->infusionEvents()
            ->whereHas('drugInfusionProfile', fn ($q) => $q->where('category', 'analgesia'))
            ->latest('occurred_at')
            ->first();

        if (! $latestOpioidEvent || $latestOpioidEvent->occurred_at->lt(now()->subHours(self::DOCUMENTATION_WINDOW_HOURS))) {
            return;
        }

        $recentPainAssessment = $icuStay->painAssessments()
            ->where('assessed_at', '>=', now()->subHours(self::DOCUMENTATION_WINDOW_HOURS))
            ->exists();

        if ($recentPainAssessment) {
            return;
        }

        $alreadyOpen = $icuStay->infusionClinicalAlerts()
            ->where('type', 'pain_assessment_overdue')
            ->where('status', '!=', 'resolved')
            ->exists();

        if ($alreadyOpen) {
            return;
        }

        InfusionClinicalAlert::create([
            'icu_stay_id' => $icuStay->id,
            'infusion_event_id' => $latestOpioidEvent->id,
            'drug_infusion_profile_id' => $latestOpioidEvent->drug_infusion_profile_id,
            'type' => 'pain_assessment_overdue',
            'severity' => 'warning',
            'message' => sprintf(
                'Infusión activa de %s sin evaluación de dolor documentada en ICU Liberation en las últimas %d horas '.
                '(bundle ABCDEF, componente "A" — evaluar/tratar el dolor). No indica dolor mal controlado, solo falta de registro.',
                $latestOpioidEvent->medication_name_raw, self::DOCUMENTATION_WINDOW_HOURS,
            ),
            'status' => 'open',
        ]);
    }
}
