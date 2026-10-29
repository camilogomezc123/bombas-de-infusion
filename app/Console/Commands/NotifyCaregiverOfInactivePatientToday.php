<?php

namespace App\Console\Commands;

use App\Enums\CaseStatus;
use App\Models\CaregiverAuthorization;
use App\Notifications\PatientNotActiveTodayNotification;
use Illuminate\Console\Command;

/**
 * Aviso del mismo día, interno de familia: si el paciente no ha entrado al portal
 * hoy, se le avisa al cuidador autorizado (no al staff — para eso ya existe
 * agora:check-portal-inactivity, que avisa al equipo clínico tras 15 días). Pensado
 * para correr en la noche, dándole al paciente todo el día para entrar antes de
 * avisarle a la familia.
 */
class NotifyCaregiverOfInactivePatientToday extends Command
{
    protected $signature = 'agora:notify-caregiver-of-inactive-patient-today';

    protected $description = 'Avisa al cuidador autorizado si el paciente no ha entrado al portal en todo el día de hoy.';

    public function handle(): int
    {
        $authorizations = CaregiverAuthorization::query()
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('last_inactivity_nudge_at')
                    ->orWhere('last_inactivity_nudge_at', '<', today());
            })
            ->whereHas('case', fn ($query) => $query->whereNotIn('status', [CaseStatus::Completed, CaseStatus::Cancelled]))
            ->with(['case.patient', 'caregiver'])
            ->get();

        $notified = 0;

        foreach ($authorizations as $authorization) {
            $patient = $authorization->case?->patient;

            if (! $patient || $patient->password === null) {
                continue;
            }

            $loggedInToday = $patient->last_login_at !== null && $patient->last_login_at->isToday();

            if ($loggedInToday) {
                continue;
            }

            $authorization->caregiver->notify(new PatientNotActiveTodayNotification($authorization->case));
            $authorization->update(['last_inactivity_nudge_at' => now()]);
            $notified++;
        }

        $this->info("Cuidadores avisados: {$notified}.");

        return self::SUCCESS;
    }
}
