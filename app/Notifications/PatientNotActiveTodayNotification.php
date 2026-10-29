<?php

namespace App\Notifications;

use App\Models\PicsCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso interno de familia (no llega al staff clínico): si el paciente no ha entrado
 * al portal en todo el día, se le avisa al cuidador autorizado para que le recuerde o
 * lo llame — mismo espíritu que la alerta al equipo por inactividad, pero del mismo
 * día y entre la propia familia, sin esperar los 15 días del umbral clínico.
 */
class PatientNotActiveTodayNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PicsCase $case) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $patientName = $this->case->patient?->full_name ?? 'tu familiar';

        return (new MailMessage)
            ->subject("👋 {$patientName} no ha entrado hoy a POSUCI 360 Conecta")
            ->greeting("Hola, {$notifiable->name}")
            ->line("{$patientName} todavía no ha abierto el portal de recuperación hoy.")
            ->line('Puede ser un buen momento para recordarle, o para preguntarle cómo se siente y ayudarle a registrarlo.')
            ->action('Abrir el portal', url('/portal'))
            ->line('Este mensaje es solo para la familia — tu equipo médico no recibe este aviso.');
    }
}
