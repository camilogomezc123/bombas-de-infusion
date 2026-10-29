<?php

namespace App\Notifications;

use App\Models\AcvCase;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CaseConversationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AcvCase $case,
        public string $title,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title)
            ->body($this->message)
            ->icon('heroicon-o-chat-bubble-left-right')
            ->iconColor('warning')
            ->actions([
                Action::make('open')
                    ->label('Abrir caso')
                    ->url(url("/acv/acv-cases/{$this->case->id}"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
