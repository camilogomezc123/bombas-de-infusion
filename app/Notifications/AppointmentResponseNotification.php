<?php

namespace App\Notifications;

use App\Models\PicsAgendaItem;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PicsAgendaItem $item) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    private function willAttend(): bool
    {
        return $this->item->patient_response === 'confirmada';
    }

    private function title(): string
    {
        $caseNumber = $this->item->case?->case_number ?? 'sin caso';
        $typeLabel = PicsAgendaItem::TYPES[$this->item->type] ?? $this->item->type;

        return $this->willAttend()
            ? "✅ {$typeLabel} confirmada · {$caseNumber}"
            : "⚠️ No podrá asistir · {$typeLabel} · {$caseNumber}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->item->scheduled_at?->format('d/m/Y H:i') ?? 'sin fecha';

        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting("Hola, {$notifiable->name}")
            ->line("El caso {$this->item->case?->case_number} respondió sobre \"{$this->item->title}\" programada para {$when}.")
            ->action('Ver agenda del caso', url('/pics/agenda-coordinada'))
            ->line('Este mensaje fue generado automáticamente por POSUCI 360 Conecta.');

        if (! $this->willAttend()) {
            $mail->line('**El paciente/cuidador indicó que no podrá asistir — considera reprogramar.**');
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $when = $this->item->scheduled_at?->format('d/m/Y H:i') ?? 'sin fecha';

        return FilamentNotification::make()
            ->title($this->title())
            ->body("{$this->item->title} · {$when}")
            ->icon($this->willAttend() ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle')
            ->iconColor($this->willAttend() ? 'success' : 'warning')
            ->actions([
                Action::make('open')
                    ->label('Ver agenda')
                    ->url(url('/pics/agenda-coordinada'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
