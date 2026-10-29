<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use App\Models\PicsAgendaItem;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgendaItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'agendaItems';

    protected static ?string $title = 'Agenda coordinada';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')->label('Tipo')->options(PicsAgendaItem::TYPES)->default('tarea')->required(),
            TextInput::make('title')->label('Título')->required()->maxLength(150)->columnSpanFull(),
            DateTimePicker::make('scheduled_at')->label('Fecha programada')->seconds(false)->required(),
            Select::make('responsible_user_id')->label('Responsable')->relationship('responsible', 'name')->searchable()->preload(),
            Textarea::make('description')->label('Descripción')->columnSpanFull(),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('Tipo')->badge()
                    ->formatStateUsing(fn (string $state): string => PicsAgendaItem::TYPES[$state] ?? $state),
                TextColumn::make('title')->label('Título')->searchable(),
                TextColumn::make('scheduled_at')->label('Programada')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('responsible.name')->label('Responsable')->placeholder('Sin asignar'),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (PicsAgendaItem $record): string => $record->statusLabel())
                    ->color(fn (string $state): string => match ($state) {
                        'completada' => 'success',
                        'cancelada' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('patient_response')->label('Respuesta del paciente')->badge()
                    ->formatStateUsing(fn (PicsAgendaItem $record): string => $record->patientResponseLabel() ?? 'Sin responder')
                    ->color(fn (?string $state): string => match ($state) {
                        'confirmada' => 'success',
                        'no_asistira' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('scheduled_at')
            ->headerActions([
                CreateAction::make()->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = auth('web')->id();

                    return $data;
                }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('complete')
                    ->label('Completar')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (PicsAgendaItem $record): bool => $record->status === 'pendiente')
                    ->requiresConfirmation()
                    ->action(function (PicsAgendaItem $record): void {
                        $record->update(['status' => 'completada', 'completed_by' => auth('web')->id(), 'completed_at' => now()]);
                        Notification::make()->success()->title('Marcado como completado')->send();
                    }),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (PicsAgendaItem $record): bool => $record->status === 'pendiente' && (auth()->user()?->canManagePicsCases() ?? false))
                    ->requiresConfirmation()
                    ->action(function (PicsAgendaItem $record): void {
                        $record->update(['status' => 'cancelada']);
                        Notification::make()->success()->title('Cancelado')->send();
                    }),
            ]);
    }
}
