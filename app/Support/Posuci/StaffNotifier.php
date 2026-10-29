<?php

namespace App\Support\Posuci;

use App\Enums\ProgramRole;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use Illuminate\Notifications\Notification;

/**
 * A quién avisar del staff cuando pasa algo en un caso PICS que requiere atención: el
 * auditor asignado si ya hay uno, o quienes lideran el programa si el caso todavía no
 * tiene auditor. Reutilizado por las notificaciones de solicitudes y de respuestas a
 * citas, para no repetir la misma resolución de destinatarios en cada observer.
 */
class StaffNotifier
{
    public static function notifyCaseStaff(PicsCase $case, Notification $notification): void
    {
        $case->loadMissing('assignedAuditor');

        if ($case->assignedAuditor) {
            $case->assignedAuditor->notify($notification);

            return;
        }

        ProgramMember::query()
            ->where('clinical_program_id', $case->clinical_program_id)
            ->where('is_active', true)
            ->whereIn('role', [ProgramRole::Leader, ProgramRole::ClinicalLeader, ProgramRole::Coordinator])
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter(fn ($user) => $user?->is_active)
            ->each(fn ($user) => $user->notify($notification));
    }
}
