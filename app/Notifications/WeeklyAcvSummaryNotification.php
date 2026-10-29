<?php

namespace App\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyAcvSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $assigned,
        public int $pending,
        public int $overdue,
    ) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Resumen semanal de casos ACV')
            ->greeting("Hola, {$notifiable->name}")
            ->line("Casos activos asignados: {$this->assigned}")
            ->line("Casos pendientes: {$this->pending}")
            ->line("Casos con más de 7 días desde la asignación: {$this->overdue}")
            ->action('Abrir mis casos', url('/admin/acv-cases'))
            ->line('Revisa y actualiza los estados para mantener los indicadores al día.');
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Resumen semanal ACV')
            ->body("Asignados: {$this->assigned}. Pendientes: {$this->pending}. Con más de 7 días: {$this->overdue}.")
            ->icon('heroicon-o-calendar-days')
            ->iconColor('primary')
            ->actions([
                Action::make('open')
                    ->label('Abrir mis casos')
                    ->url(url('/acv/egresados'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
