<?php

namespace App\Filament\Pics\Resources\RecoveryPassports\Pages;

use App\Filament\Pics\Resources\RecoveryPassports\RecoveryPassportResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRecoveryPassport extends EditRecord
{
    protected static string $resource = RecoveryPassportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label('Confirmar pasaporte')
                ->icon('heroicon-m-check-badge')
                ->color('success')
                ->visible(fn (): bool => ! $this->getRecord()->is_confirmed)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->update(['is_confirmed' => true, 'confirmed_by' => auth('web')->id(), 'confirmed_at' => now()]);
                    Notification::make()->success()->title('Pasaporte confirmado')->send();
                }),
        ];
    }
}
