<?php

namespace App\Observers;

use App\Models\ClinicalProgram;
use App\Models\InfusionClinicalAlert;
use App\Models\ProgramMember;
use App\Notifications\InfusionCriticalAlertNotification;

/**
 * Notifica al equipo del programa BOMBAS cuando se genera una alerta crítica (límite
 * duro anulado/retrocedido, dosis máxima institucional excedida o VIS crítico) — para
 * que no dependa de que alguien tenga el panel abierto en ese momento.
 */
class InfusionClinicalAlertObserver
{
    public function created(InfusionClinicalAlert $alert): void
    {
        if ($alert->severity !== 'critical') {
            return;
        }

        $program = ClinicalProgram::query()->where('code', 'BOMBAS')->first();

        if (! $program) {
            return;
        }

        $recipients = ProgramMember::query()
            ->where('clinical_program_id', $program->id)
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter(fn ($user) => $user && $user->is_active)
            ->unique('id');

        foreach ($recipients as $user) {
            $user->notify(new InfusionCriticalAlertNotification($alert));
        }
    }
}
