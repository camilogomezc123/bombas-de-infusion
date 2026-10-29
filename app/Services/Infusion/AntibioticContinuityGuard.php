<?php

namespace App\Services\Infusion;

use App\Models\DrugInfusionProfile;
use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;

/**
 * Para antibióticos en infusión continua/extendida (ej. piperacilina-tazobactam,
 * meropenem, vancomicina — los que se programan en código sepsis), lo clínicamente
 * crítico no es exceder una dosis máxima sino que la infusión NO se interrumpa:
 * una interrupción prolongada deja al paciente con niveles subterapéuticos del
 * antimicrobiano durante el episodio séptico. Se evalúa el silencio (ausencia de
 * eventos recientes), no solo el evento entrante — por eso se corre tanto tras cada
 * ingesta (InfusionClinicalEngine) como en una revisión periódica independiente
 * (ver Console\Commands\CheckAntibioticContinuity), ya que una interrupción real no
 * genera ningún evento nuevo que la dispare.
 */
class AntibioticContinuityGuard
{
    public function evaluate(IcuStay $icuStay): void
    {
        if ($icuStay->status !== 'active') {
            return;
        }

        $profiles = DrugInfusionProfile::query()->where('continuous_infusion', true)->get();

        foreach ($profiles as $profile) {
            $this->evaluateForProfile($icuStay, $profile);
        }
    }

    private function evaluateForProfile(IcuStay $icuStay, DrugInfusionProfile $profile): void
    {
        if (! $profile->max_interruption_minutes) {
            return;
        }

        $lastEvent = $icuStay->infusionEvents()
            ->where('drug_infusion_profile_id', $profile->id)
            ->latest('occurred_at')
            ->first();

        if (! $lastEvent) {
            return;
        }

        $minutesSinceLastEvent = $lastEvent->occurred_at->diffInMinutes(now());

        $alreadyOpen = $icuStay->infusionClinicalAlerts()
            ->where('type', 'antibiotic_interruption')
            ->where('drug_infusion_profile_id', $profile->id)
            ->where('status', '!=', 'resolved')
            ->exists();

        if ($minutesSinceLastEvent < $profile->max_interruption_minutes) {
            return;
        }

        if ($alreadyOpen) {
            return;
        }

        InfusionClinicalAlert::create([
            'icu_stay_id' => $icuStay->id,
            'infusion_event_id' => $lastEvent->id,
            'drug_infusion_profile_id' => $profile->id,
            'type' => 'antibiotic_interruption',
            'severity' => 'critical',
            'message' => sprintf(
                'Sin eventos de la bomba de %s desde hace %d min (umbral de continuidad: %d min) — posible interrupción '.
                'de la infusión continua. Verificar la bomba y la vía antes de asumir niveles subterapéuticos del antimicrobiano.',
                $profile->name, $minutesSinceLastEvent, $profile->max_interruption_minutes,
            ),
            'status' => 'open',
        ]);
    }
}
