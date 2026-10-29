<?php

namespace App\Filament\Pics\Resources\SupportRequests;

use App\Filament\Pics\Resources\SupportRequests\Pages\ListSupportRequests;
use App\Models\SupportRequest;
use App\Notifications\SupportRequestAnsweredNotification;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportRequestResource extends Resource
{
    protected static ?string $model = SupportRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?string $navigationLabel = 'Solicitudes y dificultades';

    protected static ?string $modelLabel = 'solicitud';

    protected static ?string $pluralModelLabel = 'Solicitudes y dificultades';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case.case_number')->label('Caso')->searchable()->sortable(),
                TextColumn::make('description')->label('Descripción')->wrap()->limit(100),
                TextColumn::make('priority')->label('Prioridad')->badge()
                    ->formatStateUsing(fn (string $state): string => SupportRequest::PRIORITIES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'alta' => 'danger', 'media' => 'warning', default => 'gray',
                    }),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (SupportRequest $record): string => $record->statusLabel())
                    ->color(fn (string $state): string => match ($state) {
                        'resuelta' => 'success', 'respondida' => 'primary', 'escalada' => 'danger',
                        'reconocida', 'asignada' => 'warning', default => 'gray',
                    }),
                TextColumn::make('assignedTo.name')->label('Asignado a')->placeholder('Sin asignar'),
                TextColumn::make('review_deadline')->label('Plazo')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('created_at')->label('Reportada')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(SupportRequest::STATUSES),
                SelectFilter::make('priority')->label('Prioridad')->options(SupportRequest::PRIORITIES),
            ])
            ->recordActions([
                Action::make('assign')
                    ->label('Asignar')
                    ->icon('heroicon-m-user-plus')
                    ->color('gray')
                    ->visible(fn (SupportRequest $record): bool => in_array($record->status, ['nueva', 'asignada'], true))
                    ->schema([
                        Select::make('assigned_to')->label('Responsable')->relationship('assignedTo', 'name')->searchable()->preload()->required(),
                        DatePicker::make('review_deadline')->label('Plazo de revisión'),
                    ])
                    ->action(function (SupportRequest $record, array $data): void {
                        $record->update(['assigned_to' => $data['assigned_to'], 'review_deadline' => $data['review_deadline'] ?? null, 'status' => 'asignada']);
                        Notification::make()->success()->title('Solicitud asignada')->send();
                    }),
                Action::make('acknowledge')
                    ->label('Reconocer recepción')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->visible(fn (SupportRequest $record): bool => in_array($record->status, ['nueva', 'asignada'], true))
                    ->action(function (SupportRequest $record): void {
                        $record->update(['status' => 'reconocida', 'acknowledged_at' => now()]);
                    }),
                Action::make('respond')
                    ->label('Responder')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->color('primary')
                    ->visible(fn (SupportRequest $record): bool => ! in_array($record->status, ['respondida', 'resuelta'], true))
                    ->schema([
                        Textarea::make('response_text')->label('Respuesta para el paciente/cuidador')->required(),
                    ])
                    ->action(function (SupportRequest $record, array $data): void {
                        $record->update([
                            'response_text' => $data['response_text'],
                            'responded_by' => auth('web')->id(),
                            'responded_at' => now(),
                            'status' => 'respondida',
                        ]);
                        $record->createdBy?->notify(new SupportRequestAnsweredNotification($record));
                        Notification::make()->success()->title('Respuesta enviada')->send();
                    }),
                Action::make('escalate')
                    ->label('Escalar')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (SupportRequest $record): bool => ! in_array($record->status, ['resuelta'], true))
                    ->requiresConfirmation()
                    ->modalDescription('Sube la prioridad a alta y marca la solicitud como escalada para que el coordinador la revise.')
                    ->action(function (SupportRequest $record): void {
                        $record->update(['status' => 'escalada', 'priority' => 'alta']);
                    }),
                Action::make('resolve')
                    ->label('Resolver')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (SupportRequest $record): bool => $record->status !== 'resuelta')
                    ->action(function (SupportRequest $record): void {
                        $record->update(['status' => 'resuelta', 'resolved_at' => now()]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportRequests::route('/'),
        ];
    }
}
