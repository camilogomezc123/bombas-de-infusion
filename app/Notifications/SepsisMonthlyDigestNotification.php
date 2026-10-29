<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SepsisMonthlyDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $summary) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->summary;

        return (new MailMessage)
            ->subject('Resumen mensual — Programa de Sepsis')
            ->greeting("Hola, {$notifiable->name}")
            ->line('Resumen del programa correspondiente a '.now()->translatedFormat('F Y').':')
            ->line("Casos del mes: {$s['period_cases']}")
            ->line("Indicadores en meta: {$s['indicators_on_target']} de ".($s['indicators_on_target'] + $s['indicators_off_target']))
            ->line("Cumplimiento de estándares: {$s['compliance_percentage']}%")
            ->line("Hallazgos abiertos: {$s['open_findings']}")
            ->line("Acciones vencidas: {$s['overdue_actions']}")
            ->line("Eventos de seguridad abiertos: {$s['open_safety_events']}")
            ->line("Evidencias/competencias por vencer en 30 días: {$s['evidence_expiring_soon']} / {$s['competencies_expiring_soon']}")
            ->action('Abrir Vista general', url('/sepsis'))
            ->line('Este mensaje fue generado automáticamente por ÁGORA.');
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Resumen mensual del Programa de Sepsis')
            ->body("Casos: {$this->summary['period_cases']}. Alertas abiertas requieren revisión.")
            ->icon('heroicon-o-chart-bar-square')
            ->iconColor('primary')
            ->getDatabaseMessage();
    }
}
