<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortalAccountInvitation extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $temporaryPassword,
        private readonly string $guard,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $role = $this->guard === 'caregiver' ? 'cuidador(a) autorizado(a)' : 'paciente';
        $name = $notifiable->name ?? $notifiable->full_name ?? '';

        return (new MailMessage)
            ->subject('Tu acceso a POSUCI 360 Conecta')
            ->greeting(trim('Hola '.$name).',')
            ->line("Tu equipo de recuperación te dio acceso a POSUCI 360 Conecta como {$role}.")
            ->line('Puedes consultar tu diario, tus metas, cómo te sientes y pedir ayuda desde ahí.')
            ->line('Usuario: '.$notifiable->email)
            ->line('Contraseña temporal: '.$this->temporaryPassword)
            ->action('Ingresar al portal', rtrim(config('app.portal_url'), '/').'/portal/login')
            ->line('Por seguridad, te pediremos cambiar esta contraseña la primera vez que ingreses.');
    }
}
