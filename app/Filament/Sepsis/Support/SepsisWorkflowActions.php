<?php

namespace App\Filament\Sepsis\Support;

use App\Enums\CaseStatus;
use App\Filament\Sepsis\Resources\SepsisCases\SepsisCaseResource;
use App\Models\SepsisCase;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class SepsisWorkflowActions
{
    /**
     * @return array<int, Action>
     */
    public static function for(SepsisCase $record): array
    {
        return [
            self::sendToAnalysis($record),
            self::finalizeAnalysis($record),
            self::finalizeReview($record),
            self::reopen($record),
        ];
    }

    private static function sendToAnalysis(SepsisCase $record): Action
    {
        return Action::make('sendToAnalysis')
            ->label('Enviar a análisis')
            ->icon('heroicon-m-paper-airplane')
            ->color('primary')
            ->visible(fn (): bool => (auth()->user()?->canManageSepsisCases() ?? false)
                && $record->status === CaseStatus::Hospitalized)
            ->requiresConfirmation()
            ->modalDescription('Se asignará al auditor y comenzará a contar el tiempo de análisis. Registra antes la fecha de egreso y el auditor.')
            ->action(function () use ($record) {
                if (! $record->discharged_at || ! $record->assigned_auditor_id) {
                    Notification::make()->danger()->title('Faltan datos')
                        ->body('Registra la fecha de egreso y asigna un auditor antes de enviar a análisis.')->send();

                    return;
                }
                $record->status = CaseStatus::Assigned;
                $record->save();
                Notification::make()->success()->title('Caso enviado a análisis')->send();

                return redirect(SepsisCaseResource::getUrl('view', ['record' => $record]));
            });
    }

    private static function finalizeAnalysis(SepsisCase $record): Action
    {
        return Action::make('finalizeAnalysis')
            ->label('Finalizar análisis')
            ->icon('heroicon-m-check')
            ->color('success')
            ->visible(function () use ($record): bool {
                $user = auth()->user();

                return $user
                    && $record->status->isAuditorEditable()
                    && ($user->canManageSepsisCases() || $record->assigned_auditor_id === $user->id);
            })
            ->requiresConfirmation()
            ->modalDescription('Se detendrá el cronómetro y el caso pasará a revisión del líder.')
            ->action(function () use ($record) {
                $record->status = CaseStatus::Pending;
                $record->save();
                Notification::make()->success()->title('Análisis finalizado, en espera de revisión')->send();

                return redirect(SepsisCaseResource::getUrl('view', ['record' => $record]));
            });
    }

    private static function finalizeReview(SepsisCase $record): Action
    {
        return Action::make('finalizeReview')
            ->label('Finalizar revisión')
            ->icon('heroicon-m-shield-check')
            ->color('success')
            ->visible(fn (): bool => (auth()->user()?->canManageSepsisCases() ?? false)
                && $record->status === CaseStatus::Pending)
            ->requiresConfirmation()
            ->modalDescription('Se cerrará el caso.')
            ->action(function () use ($record) {
                $record->status = CaseStatus::Completed;
                $record->save();
                Notification::make()->success()->title('Revisión finalizada')->send();

                return redirect(SepsisCaseResource::getUrl('view', ['record' => $record]));
            });
    }

    private static function reopen(SepsisCase $record): Action
    {
        return Action::make('reopen')
            ->label('Reabrir caso')
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('gray')
            ->visible(fn (): bool => (auth()->user()?->canManageSepsisCases() ?? false)
                && in_array($record->status, [CaseStatus::Pending, CaseStatus::Completed], true))
            ->requiresConfirmation()
            ->modalDescription('Uso excepcional: el caso vuelve a "En revisión" para que el auditor pueda corregirlo.')
            ->action(function () use ($record) {
                $record->status = CaseStatus::InReview;
                $record->save();
                Notification::make()->success()->title('Caso reabierto')->send();

                return redirect(SepsisCaseResource::getUrl('view', ['record' => $record]));
            });
    }
}
