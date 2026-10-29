<?php

namespace App\Filament\Acv\Resources\AcvCases\RelationManagers;

use App\Enums\UserRole;
use App\Models\AcvCase;
use App\Models\CaseComment;
use App\Models\User;
use App\Notifications\CaseConversationNotification;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Conversación del caso';

    /**
     * Filament hace de solo lectura los relation managers en la página "Ver" por
     * defecto. Los comentarios deben poder escribirse también desde ahí (los
     * auditores acceden principalmente por "Ver", no por "Editar").
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->label('Comentario')
                    ->required()
                    ->rows(4)
                    ->maxLength(5000),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('thread')
                    ->label('Hilo')
                    ->state(fn (CaseComment $record): string => $record->parent_id ? 'Respuesta' : 'Mensaje')
                    ->badge()
                    ->color(fn (CaseComment $record): string => $record->parent_id ? 'gray' : 'primary'),
                TextColumn::make('user.name')
                    ->label('Autor')
                    ->badge(),
                TextColumn::make('body')
                    ->label('Comentario')
                    ->wrap()
                    ->searchable()
                    ->description(fn (CaseComment $record): ?string => $record->parent_id
                        ? '↳ en respuesta a '.($record->parent?->user?->name ?? 'mensaje eliminado')
                            .': "'.Str::limit((string) $record->parent?->body, 60).'"'
                        : null),
                IconColumn::make('is_resolved')
                    ->label('Resuelto')
                    ->boolean(),
                TextColumn::make('resolvedBy.name')
                    ->label('Resuelto por')
                    ->placeholder('Pendiente'),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar comentario')
                    ->modalHeading('Nuevo mensaje')
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['parent_id'] = null;

                        return $data;
                    })
                    ->after(function (CaseComment $record): void {
                        $this->notifyAboutComment($record);
                    }),
            ])
            ->recordActions([
                Action::make('reply')
                    ->label('Responder')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('primary')
                    ->visible(fn (CaseComment $record): bool => $record->parent_id === null)
                    ->form([
                        Textarea::make('body')
                            ->label('Respuesta')
                            ->required()
                            ->rows(4)
                            ->maxLength(5000),
                    ])
                    ->action(function (CaseComment $record, array $data): void {
                        $reply = $this->getOwnerRecord()->comments()->create([
                            'user_id' => auth()->id(),
                            'parent_id' => $record->id,
                            'body' => $data['body'],
                        ]);

                        $this->notifyAboutComment($reply);
                    }),
                EditAction::make()
                    ->visible(fn (CaseComment $record): bool => ! $record->is_resolved && (
                        $record->user_id === auth()->id() || auth()->user()->canManageAllCases()
                    )),
                Action::make('resolve')
                    ->label('Marcar resuelto')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (CaseComment $record): bool => $record->parent_id === null && ! $record->is_resolved)
                    ->action(fn (CaseComment $record) => $record->update([
                        'is_resolved' => true,
                        'resolved_by' => auth()->id(),
                        'resolved_at' => now(),
                    ])),
                Action::make('reopen')
                    ->label('Reabrir')
                    ->icon('heroicon-m-arrow-path')
                    ->visible(fn (CaseComment $record): bool => $record->parent_id === null
                        && $record->is_resolved
                        && auth()->user()->canManageAllCases())
                    ->action(fn (CaseComment $record) => $record->update([
                        'is_resolved' => false,
                        'resolved_by' => null,
                        'resolved_at' => null,
                    ])),
            ])
            // Agrupa cada hilo (mensaje raíz + sus respuestas) y lo ordena
            // cronológicamente; los hilos en sí también quedan en orden de creación.
            ->modifyQueryUsing(fn ($query) => $query
                ->reorder()
                ->orderByRaw('COALESCE(parent_id, id) asc')
                ->orderBy('created_at', 'asc'));
    }

    /**
     * Notifica al líder/auditor correspondiente y, si aplica, al autor del
     * mensaje al que se está respondiendo.
     */
    private function notifyAboutComment(CaseComment $comment): void
    {
        /** @var AcvCase $case */
        $case = $this->getOwnerRecord();
        $case->loadMissing(['patient', 'assignedAuditor']);
        $actor = auth()->user();

        if (! $actor) {
            return;
        }

        $notification = new CaseConversationNotification(
            $case,
            "Comentario en {$case->case_number}",
            Str::limit("{$actor->name}: {$comment->body}", 120),
        );

        $notified = collect([$actor->id]);

        // Si es una respuesta, el autor del mensaje original siempre se entera.
        if ($comment->parent_id) {
            $parentAuthor = $comment->parent?->user;

            if ($parentAuthor && ! $notified->contains($parentAuthor->id)) {
                $parentAuthor->notify($notification);
                $notified->push($parentAuthor->id);
            }
        }

        if ($actor->canManageAllCases()) {
            // Líder/admin comenta → notificar al auditor asignado
            if ($case->assignedAuditor && ! $notified->contains($case->assignedAuditor->id)) {
                $case->assignedAuditor->notify($notification);
            }

            return;
        }

        // Auditor comenta → notificar a todos los líderes/admins activos
        User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::Administrator->value, UserRole::Leader->value])
            ->whereNotIn('id', $notified->all())
            ->each(fn (User $u) => $u->notify($notification));
    }
}
