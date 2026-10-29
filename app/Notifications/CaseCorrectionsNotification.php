<?php

namespace App\Notifications;

use App\Models\AcvCase;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseCorrectionsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array{label: string, old: ?string, new: ?string}>  $corrections
     */
    public function __construct(public AcvCase $case, public array $corrections = []) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Revisión finalizada · Caso ACV {$this->case->case_number}")
            ->greeting("Hola, {$notifiable->name}")
            ->line("El líder finalizó la revisión del caso {$this->case->case_number} (paciente: {$this->case->patient->full_name}).");

        if (empty($this->corrections)) {
            $mail->line('No se realizaron correcciones: el caso quedó aprobado tal como lo dejaste.');
        } else {
            $mail->line('Se realizaron las siguientes correcciones para tu retroalimentación:');

            foreach ($this->corrections as $correction) {
                $mail->line("• {$correction['label']}: «".($correction['old'] ?? '—').'» → «'.($correction['new'] ?? '—').'»');
            }
        }

        return $mail
            ->action('Abrir caso en ÁGORA', url("/acv/acv-cases/{$this->case->id}"))
            ->line('Este mensaje fue generado automáticamente por ÁGORA.');
    }

    public function toArray(object $notifiable): array
    {
        $message = empty($this->corrections)
                ? "El líder finalizó la revisión del caso {$this->case->case_number} sin correcciones."
                : "El líder finalizó la revisión del caso {$this->case->case_number} con ".count($this->corrections).' corrección(es).';

        return FilamentNotification::make()
            ->title("Revisión finalizada · {$this->case->case_number}")
            ->body($message)
            ->icon('heroicon-o-check-badge')
            ->iconColor('success')
            ->actions([
                Action::make('open')
                    ->label('Abrir caso')
                    ->url(url("/acv/acv-cases/{$this->case->id}"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
