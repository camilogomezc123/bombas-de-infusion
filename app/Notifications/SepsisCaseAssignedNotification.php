<?php

namespace App\Notifications;

use App\Models\SepsisCase;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SepsisCaseAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SepsisCase $case) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Caso Sepsis {$this->case->case_number} asignado")
            ->greeting("Hola, {$notifiable->name}")
            ->line("Se te asignó el caso {$this->case->case_number} para análisis.")
            ->action('Abrir caso en ÁGORA', url("/sepsis/sepsis-cases/{$this->case->id}/edit"))
            ->line('Este mensaje fue generado automáticamente por ÁGORA.');
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title("Caso Sepsis {$this->case->case_number} asignado")
            ->body("Tienes asignado el caso {$this->case->case_number} para análisis.")
            ->icon('heroicon-o-user-plus')
            ->iconColor('primary')
            ->actions([
                Action::make('open')
                    ->label('Abrir caso')
                    ->url(url("/sepsis/sepsis-cases/{$this->case->id}/edit"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
