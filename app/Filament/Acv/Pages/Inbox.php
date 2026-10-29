<?php

namespace App\Filament\Acv\Pages;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\CaseConversationNotification;
use App\Notifications\CaseCorrectionsNotification;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Bandeja de solicitudes: separa los casos asignados (acción requerida) de los
 * comentarios y demás actividad de los casos, en lugar de mezclarlo todo en la
 * campanita de notificaciones.
 */
class Inbox extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.case-list';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $navigationLabel = 'Bandeja';

    protected static ?string $title = 'Bandeja de solicitudes';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'bandeja';

    private const ASSIGNMENT_TYPES = [
        CaseAssignedNotification::class,
    ];

    private const ACTIVITY_TYPES = [
        CaseConversationNotification::class,
        CaseCorrectionsNotification::class,
    ];

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::Administrator,
            UserRole::Leader,
            UserRole::Auditor,
        ], true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', auth()->id())
            )
            ->columns([
                TextColumn::make('category')
                    ->label('Tipo')
                    ->state(fn (DatabaseNotification $record): string => in_array($record->type, self::ASSIGNMENT_TYPES, true)
                        ? 'Caso asignado'
                        : 'Comentario / actividad')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Caso asignado' ? 'primary' : 'warning'),
                TextColumn::make('data.title')
                    ->label('Asunto')
                    ->weight('bold')
                    ->wrap(),
                TextColumn::make('data.body')
                    ->label('Mensaje')
                    ->wrap()
                    ->limit(140),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                IconColumn::make('read_at')
                    ->label('Leído')
                    ->boolean()
                    ->state(fn (DatabaseNotification $record): bool => $record->read_at !== null),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Tipo')
                    ->options([
                        'assigned' => 'Casos asignados',
                        'activity' => 'Comentarios y actividad',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'assigned' => $query->whereIn('type', self::ASSIGNMENT_TYPES),
                            'activity' => $query->whereIn('type', self::ACTIVITY_TYPES),
                            default => $query,
                        };
                    }),
                TernaryFilter::make('read')
                    ->label('Leído')
                    ->placeholder('Todas')
                    ->trueLabel('Leídas')
                    ->falseLabel('No leídas')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('read_at'),
                        false: fn (Builder $query) => $query->whereNull('read_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Abrir')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->visible(fn (DatabaseNotification $record): bool => filled($record->data['actions'][0]['url'] ?? null))
                    ->action(function (DatabaseNotification $record) {
                        $record->markAsRead();

                        return redirect($record->data['actions'][0]['url']);
                    }),
                Action::make('toggle_read')
                    ->label(fn (DatabaseNotification $record): string => $record->read_at ? 'Marcar no leída' : 'Marcar leída')
                    ->icon(fn (DatabaseNotification $record): string => $record->read_at ? 'heroicon-m-envelope' : 'heroicon-m-envelope-open')
                    ->color('gray')
                    ->action(function (DatabaseNotification $record): void {
                        $record->read_at
                            ? $record->update(['read_at' => null])
                            : $record->markAsRead();
                    }),
            ])
            ->headerActions([
                Action::make('mark_all_read')
                    ->label('Marcar todas como leídas')
                    ->icon('heroicon-m-check-circle')
                    ->color('gray')
                    ->action(function (): void {
                        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('Sin notificaciones');
    }
}
