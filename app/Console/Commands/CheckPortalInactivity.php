<?php

namespace App\Console\Commands;

use App\Enums\CaseStatus;
use App\Models\PicsCase;
use App\Notifications\PortalInactivityNotification;
use App\Services\PortalEngagementService;
use App\Support\Posuci\StaffNotifier;
use Illuminate\Console\Command;

/**
 * Convierte en aviso proactivo lo que antes solo se veía si el staff entraba a
 * revisar la pantalla "Trazabilidad del portal" (PortalEngagementService::
 * inactivityAlerts) — así una racha rota del paciente tiene una consecuencia real
 * de cuidado (alguien del equipo se entera y puede llamar) en vez de solo perderse.
 *
 * No repite el aviso todos los días para el mismo caso: una vez notificado, espera
 * al menos 7 días antes de volver a avisar por el mismo caso (last_inactivity_alert_at).
 */
class CheckPortalInactivity extends Command
{
    private const COOLDOWN_DAYS = 7;

    protected $signature = 'agora:check-portal-inactivity';

    protected $description = 'Notifica al equipo clínico cuando un caso PICS lleva varios días sin actividad en el portal.';

    public function handle(PortalEngagementService $service): int
    {
        $cases = PicsCase::query()
            ->whereNotIn('status', [CaseStatus::Completed, CaseStatus::Cancelled])
            ->where(function ($query) {
                $query->whereNull('last_inactivity_alert_at')
                    ->orWhere('last_inactivity_alert_at', '<', now()->subDays(self::COOLDOWN_DAYS));
            })
            ->with([
                'patient', 'caregiverAuthorizations.caregiver', 'diaryEntries',
                'recoveryGoals.progressReports', 'followups', 'supportRequests',
                'recoveryPassport', 'carePlan', 'caregiverJourneySteps',
                'dischargeReadinessCheck.items', 'medicationReconciliation.items',
                'homeMonitoringReadings', 'educationAssignments', 'assignedAuditor',
            ])
            ->get();

        $alertsByCase = collect($service->inactivityAlerts($cases))->groupBy(fn (array $alert) => $alert['case']->id);

        foreach ($alertsByCase as $caseAlerts) {
            /** @var PicsCase $case */
            $case = $caseAlerts->first()['case'];
            $reasons = $caseAlerts->pluck('reason')->unique()->values()->all();

            StaffNotifier::notifyCaseStaff($case, new PortalInactivityNotification($case, $reasons));
            $case->update(['last_inactivity_alert_at' => now()]);
        }

        $this->info("Casos notificados por inactividad: {$alertsByCase->count()}.");

        return self::SUCCESS;
    }
}
