<?php

namespace App\Filament\Acv\Support;

use App\Enums\CaseStatus;
use App\Filament\Acv\Resources\AcvCases\AcvCaseResource;
use App\Models\AcvCase;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Component;

/**
 * Acciones del flujo de un caso ACV (enviar a análisis, finalizar análisis,
 * finalizar revisión, reabrir). Se reutilizan en las páginas Ver y Editar.
 */
class CaseWorkflowActions
{
    /**
     * @return array<int, Action>
     */
    public static function for(AcvCase $record): array
    {
        return [
            self::sendToAnalysis($record),
            self::finalizeAnalysis($record),
            self::finalizeReview($record),
            self::reopen($record),
        ];
    }

    private static function sendToAnalysis(AcvCase $record): Action
    {
        return Action::make('sendToAnalysis')
            ->label('Enviar a análisis')
            ->icon('heroicon-m-paper-airplane')
            ->color('primary')
            ->visible(fn (): bool => (auth()->user()?->canManageAllCases() ?? false)
                && $record->status === CaseStatus::Hospitalized)
            ->requiresConfirmation()
            ->modalHeading('Enviar caso a análisis')
            ->modalDescription('Se asignará al auditor y comenzará a contar el tiempo de análisis. Asegúrate de haber registrado la fecha de egreso y el auditor.')
            ->action(function (?Component $livewire = null) use ($record) {
                // El formulario puede tener cambios sin guardar (p. ej. la fecha de
                // egreso recién diligenciada); los persistimos antes de validar.
                if ($livewire instanceof EditRecord) {
                    $livewire->save(shouldRedirect: false, shouldSendSavedNotification: false);
                }

                $record->refresh();

                if (! $record->discharged_at || ! $record->assigned_auditor_id) {
                    Notification::make()
                        ->danger()
                        ->title('Faltan datos')
                        ->body('Registra la fecha de egreso y asigna un auditor antes de enviar a análisis.')
                        ->send();

                    return;
                }

                $record->status = CaseStatus::Assigned;
                $record->save();

                Notification::make()->success()->title('Caso enviado a análisis')->send();

                return redirect(AcvCaseResource::getUrl('view', ['record' => $record]));
            });
    }

    private static function finalizeAnalysis(AcvCase $record): Action
    {
        return Action::make('finalizeAnalysis')
            ->label('Finalizar análisis')
            ->icon('heroicon-m-check')
            ->color('success')
            ->visible(function () use ($record): bool {
                $user = auth()->user();

                return $user
                    && $record->status->isAuditorEditable()
                    && ($user->canManageAllCases() || $record->assigned_auditor_id === $user->id);
            })
            ->requiresConfirmation()
            ->modalHeading('Finalizar análisis')
            ->modalDescription('Se detendrá el cronómetro y el caso pasará a revisión del líder. Ya no podrás editarlo salvo que el líder lo reabra.')
            ->action(function () use ($record) {
                $record->status = CaseStatus::Pending;
                $record->save();

                Notification::make()->success()->title('Análisis finalizado, en espera de revisión')->send();

                return redirect(AcvCaseResource::getUrl('view', ['record' => $record]));
            });
    }

    private static function finalizeReview(AcvCase $record): Action
    {
        return Action::make('finalizeReview')
            ->label('Finalizar revisión')
            ->icon('heroicon-m-shield-check')
            ->color('success')
            ->visible(fn (): bool => (auth()->user()?->canManageAllCases() ?? false)
                && $record->status === CaseStatus::Pending)
            ->requiresConfirmation()
            ->modalHeading('Finalizar revisión')
            ->modalDescription('Se cerrará el caso y se enviará al auditor el detalle de las correcciones realizadas.')
            ->action(function () use ($record) {
                $record->status = CaseStatus::Completed;
                $record->save();

                Notification::make()->success()->title('Revisión finalizada')->send();

                return redirect(AcvCaseResource::getUrl('view', ['record' => $record]));
            });
    }

    private static function reopen(AcvCase $record): Action
    {
        return Action::make('reopen')
            ->label('Reabrir caso')
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('gray')
            ->visible(fn (): bool => (auth()->user()?->canManageAllCases() ?? false)
                && in_array($record->status, [CaseStatus::Pending, CaseStatus::Completed], true))
            ->requiresConfirmation()
            ->modalHeading('Reabrir caso')
            ->modalDescription('Uso excepcional: el caso vuelve a "En revisión" para que el auditor pueda corregirlo.')
            ->action(function () use ($record) {
                $record->status = CaseStatus::InReview;
                $record->save();

                Notification::make()->success()->title('Caso reabierto')->send();

                return redirect(AcvCaseResource::getUrl('view', ['record' => $record]));
            });
    }
}
