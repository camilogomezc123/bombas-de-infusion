<?php

namespace App\Notifications;

use App\Models\InfusionClinicalAlert;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InfusionCriticalAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public InfusionClinicalAlert $alert) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $stay = $this->alert->icuStay;

        return (new MailMessage)
            ->subject('Alerta crítica de bomba de infusión'.($stay ? " — {$stay->case_number}" : ''))
            ->greeting("Hola, {$notifiable->name}")
            ->line($this->alert->message)
            ->line('Cama: '.($stay?->bed_label ?? 'sin asignar'))
            ->action('Ver alertas', url('/infusion-monitoring/infusion-clinical-alerts'))
            ->line('Este mensaje fue generado automáticamente por ÁGORA. No sustituye el monitoreo clínico directo.');
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Alerta crítica de bomba de infusión')
            ->body($this->alert->message)
            ->icon('heroicon-o-bell-alert')
            ->iconColor('danger')
            ->actions([
                Action::make('open')
                    ->label('Ver alertas')
                    ->url('/infusion-monitoring/infusion-clinical-alerts')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
