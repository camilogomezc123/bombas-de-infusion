<?php

namespace App\Notifications;

use App\Models\CommitteeMeeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommitteeMeetingScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CommitteeMeeting $meeting,
        public string $attendeeName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $scheduledAt = $this->meeting->scheduled_at?->timezone('America/Bogota');

        return (new MailMessage)
            ->subject("Convocatoria: {$this->meeting->title}")
            ->greeting("Hola, {$this->attendeeName}")
            ->line("Quedas convocado a la reunión \"{$this->meeting->title}\" del comité {$this->meeting->committee?->name}.")
            ->when($scheduledAt, fn (MailMessage $mail): MailMessage => $mail->line(
                'Fecha y hora: '.$scheduledAt->translatedFormat('d \d\e F \d\e Y, h:i A'),
            ))
            ->when(filled($this->meeting->agenda), fn (MailMessage $mail): MailMessage => $mail
                ->line('Orden del día:')
                ->line($this->meeting->agenda))
            ->line('Este mensaje fue generado automáticamente por ÁGORA.');
    }
}
