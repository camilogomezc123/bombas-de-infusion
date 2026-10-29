<?php

namespace App\Console\Commands;

use App\Enums\CaseStatus;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Support\Posuci\CaseAccess;
use App\Support\Posuci\WebPushSender;
use Illuminate\Console\Command;

/**
 * Empuja un recordatorio matutino real a quien haya activado las notificaciones push
 * del portal — la palanca más directa para que entren sin tener que acordarse
 * solos, porque llega aunque el navegador esté cerrado. Solo alcanza a quien ya se
 * suscribió desde el botón "🔔 Activar notificaciones"; sin llaves VAPID
 * configuradas (VAPID_PUBLIC_KEY/VAPID_PRIVATE_KEY) no hace nada.
 */
class SendDailyPortalPush extends Command
{
    protected $signature = 'agora:send-daily-portal-push';

    protected $description = 'Manda una notificación push matutina a pacientes/cuidadores con notificaciones activadas.';

    public function handle(WebPushSender $sender): int
    {
        if (! $sender->isConfigured()) {
            $this->warn('VAPID_PUBLIC_KEY/VAPID_PRIVATE_KEY no están configuradas — nada que enviar.');

            return self::SUCCESS;
        }

        $sent = 0;

        Patient::query()->whereHas('pushSubscriptions')->each(function (Patient $patient) use ($sender, &$sent) {
            if (! $this->hasActiveCase(CaseAccess::currentCaseForPatient($patient))) {
                return;
            }

            $sent += $sender->sendToActor(
                $patient,
                'Buenos días 👋',
                '¿Cómo amaneciste? Toca para ver tu día en POSUCI 360 Conecta.',
            );
        });

        Caregiver::query()->whereHas('pushSubscriptions')->each(function (Caregiver $caregiver) use ($sender, &$sent) {
            if (! $this->hasActiveCase(CaseAccess::currentCaseForCaregiver($caregiver))) {
                return;
            }

            $sent += $sender->sendToActor(
                $caregiver,
                'Buenos días 👋',
                'Revisa cómo va tu familiar hoy en POSUCI 360 Conecta.',
            );
        });

        $this->info("Notificaciones push enviadas: {$sent}.");

        return self::SUCCESS;
    }

    private function hasActiveCase(?PicsCase $case): bool
    {
        return $case !== null && ! in_array($case->status, [CaseStatus::Completed, CaseStatus::Cancelled], true);
    }
}
