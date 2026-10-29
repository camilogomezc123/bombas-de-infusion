<?php

namespace App\Notifications;

use App\Models\SupportRequest;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportRequestReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportRequest $request) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    private function isUrgent(): bool
    {
        return $this->request->priority === 'alta';
    }

    private function title(): string
    {
        $caseNumber = $this->request->case?->case_number ?? 'sin caso';
        $typeLabel = SupportRequest::TYPES[$this->request->type] ?? $this->request->type;

        return $this->isUrgent()
            ? "Urgente · {$typeLabel} · {$caseNumber}"
            : "Nueva solicitud · {$typeLabel} · {$caseNumber}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting("Hola, {$notifiable->name}")
            ->line("El paciente o cuidador del caso {$this->request->case?->case_number} reportó una solicitud nueva.")
            ->line('Descripción: '.$this->request->description)
            ->action('Revisar solicitud', url('/pics/support-requests'))
            ->line('Este mensaje fue generado automáticamente por POSUCI 360 Conecta.');

        if ($this->isUrgent()) {
            $mail->line('**Esta solicitud fue marcada como urgente por quien la reportó.**');
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title())
            ->body($this->request->description)
            ->icon($this->isUrgent() ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-lifebuoy')
            ->iconColor($this->isUrgent() ? 'danger' : 'primary')
            ->actions([
                Action::make('open')
                    ->label('Ver solicitud')
                    ->url(url('/pics/support-requests'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
