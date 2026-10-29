<?php

namespace App\Notifications;

use App\Models\SupportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Avisa al paciente/cuidador que creó la solicitud cuando el equipo responde —
 * alimenta el centro de notificaciones del portal (Patient/Caregiver ya son
 * Notifiable vía PortalAccountAuthenticatable, así que esto se guarda en su propia
 * bandeja igual que cualquier notificación de Laravel).
 */
class SupportRequestAnsweredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportRequest $request) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('💬 Tu equipo respondió tu solicitud')
            ->greeting("Hola, {$this->greetingName($notifiable)}")
            ->line('Le preguntaste a tu equipo:')
            ->line("\"{$this->request->description}\"")
            ->line('Y respondieron:')
            ->line($this->request->response_text ?? '')
            ->action('Ver en el portal', url('/portal/ayuda'))
            ->line('Este mensaje fue generado automáticamente por POSUCI 360 Conecta.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => '💬 Tu equipo respondió tu solicitud',
            'body' => Str::limit($this->request->response_text ?? '', 120),
            'url' => '/portal/ayuda',
        ];
    }

    private function greetingName(object $notifiable): string
    {
        return $notifiable->full_name ?? $notifiable->name ?? '';
    }
}
