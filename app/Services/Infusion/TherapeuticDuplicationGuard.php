<?php

namespace App\Services\Infusion;

use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;

/**
 * Detecta dos fármacos DISTINTOS de la misma clase activos a la vez para la misma
 * estancia — deliberadamente acotado a analgesia y sedación, donde duplicar suele ser
 * un error de prescripción/transcripción (ej. fentanilo + morfina simultáneos sin que
 * uno sea el reemplazo documentado del otro). Los vasoactivos se excluyen a propósito:
 * combinar dos o más (ej. norepinefrina + vasopresina) es terapia estándar en shock
 * refractario, no un error — alertar ahí produciría ruido constante y sin valor clínico.
 */
class TherapeuticDuplicationGuard
{
    private const WATCHED_CATEGORIES = ['analgesia', 'sedation'];

    private const ACTIVE_WINDOW_HOURS = 2;

    public function evaluate(IcuStay $icuStay): void
    {
        if ($icuStay->status !== 'active') {
            return;
        }

        foreach (self::WATCHED_CATEGORIES as $category) {
            $this->evaluateCategory($icuStay, $category);
        }
    }

    private function evaluateCategory(IcuStay $icuStay, string $category): void
    {
        $activeDrugNames = $icuStay->infusionEvents()
            ->whereHas('drugInfusionProfile', fn ($q) => $q->where('category', $category))
            ->where('occurred_at', '>=', now()->subHours(self::ACTIVE_WINDOW_HOURS))
            ->with('drugInfusionProfile')
            ->get()
            ->pluck('drugInfusionProfile.id', 'drugInfusionProfile.name')
            ->filter();

        if ($activeDrugNames->count() < 2) {
            return;
        }

        $categoryLabel = $category === 'analgesia' ? 'analgésico opioide' : 'sedante';

        $alreadyOpen = $icuStay->infusionClinicalAlerts()
            ->where('type', 'therapeutic_duplication')
            ->where('status', '!=', 'resolved')
            ->where('message', 'like', '%'.$categoryLabel.'%')
            ->exists();

        if ($alreadyOpen) {
            return;
        }

        InfusionClinicalAlert::create([
            'icu_stay_id' => $icuStay->id,
            'drug_infusion_profile_id' => $activeDrugNames->first(),
            'type' => 'therapeutic_duplication',
            'severity' => 'warning',
            'message' => sprintf(
                'Más de un %s activo a la vez para esta estancia: %s. Verificar si es intencional (ej. rotación documentada) o una duplicidad de prescripción.',
                $categoryLabel, $activeDrugNames->keys()->implode(' + '),
            ),
            'status' => 'open',
        ]);
    }
}
