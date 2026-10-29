<?php

namespace App\Notifications;

use App\Models\PicsCase;
use App\Services\PortalEngagementService;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa al equipo cuando un caso lleva varios días sin actividad de portal (o el
 * cuidador autorizado nunca ha ingresado) — mismos criterios que ya calculaba
 * PortalEngagementService::inactivityAlerts() para la pantalla "Trazabilidad del
 * portal", ahora empujados de forma proactiva en vez de esperar a que alguien entre
 * a revisar esa pantalla.
 */
class PortalInactivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, string>  $reasons
     */
    public function __construct(public PicsCase $case, public array $reasons) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    private function reasonLabels(): array
    {
        return array_map(fn (string $reason) => PortalEngagementService::ALERT_LABELS[$reason] ?? $reason, $this->reasons);
    }

    private function title(): string
    {
        return "🔕 Caso {$this->case->case_number} sin actividad en el portal";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting("Hola, {$notifiable->name}")
            ->line("El caso {$this->case->case_number} necesita seguimiento:");

        foreach ($this->reasonLabels() as $label) {
            $mail->line("• {$label}");
        }

        return $mail
            ->line('Puede valer la pena una llamada para saber cómo va el paciente y recordarle usar el portal.')
            ->action('Ver caso', url("/pics/pics-cases/{$this->case->id}"))
            ->line('Este mensaje fue generado automáticamente por POSUCI 360 Conecta.');
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title())
            ->body(implode(' · ', $this->reasonLabels()))
            ->icon('heroicon-o-bell-alert')
            ->iconColor('warning')
            ->actions([
                Action::make('open')
                    ->label('Ver caso')
                    ->url(url("/pics/pics-cases/{$this->case->id}"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
