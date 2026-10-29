<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortalResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
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
        $url = rtrim(config('app.portal_url'), '/').route('portal.password.reset', [
            'token' => $this->token,
            'guard' => $this->guard,
            'email' => $notifiable->email,
        ], false);

        return (new MailMessage)
            ->subject('Restablece tu contraseña de POSUCI 360 Conecta')
            ->line('Recibimos una solicitud para restablecer tu contraseña.')
            ->action('Restablecer contraseña', $url)
            ->line('Si no solicitaste esto, puedes ignorar este mensaje — tu contraseña no cambiará.');
    }
}
