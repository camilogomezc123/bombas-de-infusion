<?php

namespace App\Filament\Sepsis\Resources\ProgramCommittees\RelationManagers;

use App\Notifications\CommitteeMeetingScheduledNotification;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Storage;

class MeetingsRelationManager extends RelationManager
{
    protected static string $relationship = 'meetings';

    protected static ?string $title = 'Reuniones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Título')->required()->columnSpanFull(),
            DateTimePicker::make('scheduled_at')->label('Fecha y hora')->timezone('America/Bogota')->required(),
            Select::make('status')->label('Estado')->options([
                'scheduled' => 'Programada', 'completed' => 'Realizada', 'cancelled' => 'Cancelada',
            ])->required(),
            Select::make('attendees')
                ->label('Invitados')
                ->relationship('attendees', 'display_name')
                ->multiple()
                ->preload()
                ->default(fn (): array => $this->getOwnerRecord()->members()->where('is_active', true)->pluck('id')->all())
                ->helperText('Se preseleccionan automáticamente los integrantes activos del comité.')
                ->columnSpanFull(),
            Textarea::make('agenda')->label('Orden del día')->columnSpanFull(),
            Textarea::make('minutes')->label('Acta (texto)')->columnSpanFull(),
            FileUpload::make('minutes_file_path')
                ->label('Acta firmada (PDF)')
                ->disk('local')
                ->directory('committee-minutes')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(10240)
                ->downloadable()
                ->openable()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Reunión'),
                TextColumn::make('scheduled_at')->label('Fecha')->dateTime('d/m/Y H:i', timezone: 'America/Bogota'),
                TextColumn::make('status')->label('Estado')->badge()->icon('heroicon-m-information-circle'),
                TextColumn::make('attendees_count')->label('Invitados')->counts('attendees'),
                TextColumn::make('minutes_file_path')->label('Acta')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Cargada' : 'Sin cargar')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('sendInvitation')
                    ->label('Enviar convocatoria')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalDescription('Se enviará un correo con la fecha, hora y orden del día a cada invitado que tenga correo electrónico registrado.')
                    ->visible(fn ($record): bool => $record->attendees()->exists())
                    ->action(function ($record): void {
                        $sent = 0;

                        foreach ($record->attendees as $member) {
                            $email = $member->notificationEmail();

                            if (! $email) {
                                continue;
                            }

                            NotificationFacade::route('mail', $email)
                                ->notify(new CommitteeMeetingScheduledNotification($record, $member->display_name));
                            $sent++;
                        }

                        Notification::make()
                            ->title($sent > 0 ? "Convocatoria enviada a {$sent} invitado(s)" : 'Ningún invitado tiene correo registrado')
                            ->{$sent > 0 ? 'success' : 'warning'}()
                            ->send();
                    }),
                Action::make('downloadMinutes')
                    ->label('Descargar acta')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn ($record): bool => filled($record->minutes_file_path))
                    ->action(fn ($record) => response()->download(Storage::disk('local')->path($record->minutes_file_path))),
                EditAction::make(),
            ]);
    }
}
