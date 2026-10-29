<?php

namespace App\Observers;

use App\Models\SupportRequest;
use App\Notifications\SupportRequestReceivedNotification;
use App\Support\Posuci\StaffNotifier;

/**
 * Avisa al staff cuando el paciente o el cuidador reportan una solicitud nueva — antes
 * nadie se enteraba sin entrar manualmente a revisar la lista, así que una dificultad
 * urgente podía quedar sin respuesta. Se notifica al auditor asignado al caso; si el
 * caso todavía no tiene auditor asignado, se notifica a quienes lideran el programa.
 */
class SupportRequestObserver
{
    public function created(SupportRequest $request): void
    {
        $request->loadMissing('case');
        $case = $request->case;

        if (! $case) {
            return;
        }

        StaffNotifier::notifyCaseStaff($case, new SupportRequestReceivedNotification($request));
    }
}
