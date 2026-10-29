<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use App\Models\EducationAssignment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EducationAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'educationAssignments';

    protected static ?string $title = 'Educación personalizada';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('education_resource_id')
                ->label('Contenido')
                ->relationship('resource', 'title', fn ($query) => $query->where('is_active', true))
                ->searchable()->preload()->required(),
            Textarea::make('notes')->label('Por qué aplica a este paciente (opcional)')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('resource.title')->label('Contenido')->searchable(),
                TextColumn::make('resource.category')->label('Categoría')
                    ->formatStateUsing(fn (EducationAssignment $record): string => $record->resource?->categoryLabel() ?? '—'),
                TextColumn::make('assignedBy.name')->label('Asignado por')->placeholder('—'),
                IconColumn::make('viewed_at')->label('Leído')->boolean()
                    ->getStateUsing(fn (EducationAssignment $record): bool => $record->isViewed()),
                TextColumn::make('response')->label('Respuesta')->badge()->placeholder('Sin respuesta')
                    ->formatStateUsing(fn (?string $state): string => EducationAssignment::RESPONSES[$state] ?? 'Sin respuesta')
                    ->color(fn (?string $state): string => match ($state) {
                        'comprendido' => 'success',
                        'necesito_apoyo' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('feedback')->label('Comentario')->wrap()->limit(80)->placeholder('—'),
                TextColumn::make('completedBy.name')->label('Completado por')
                    ->getStateUsing(fn (EducationAssignment $record): string => $record->completedBy?->name ?? $record->completedBy?->full_name ?? '—'),
                TextColumn::make('completed_at')->label('Completado')->dateTime('d/m/Y H:i')->placeholder('Pendiente'),
                IconColumn::make('validated_at')->label('Validado')->boolean()
                    ->getStateUsing(fn (EducationAssignment $record): bool => $record->isValidated()),
                TextColumn::make('validatedBy.name')->label('Validado por')->placeholder('—'),
            ])
            ->defaultSort('assigned_at', 'desc')
            ->headerActions([
                CreateAction::make()->mutateFormDataUsing(function (array $data): array {
                    $data['assigned_by'] = auth('web')->id();
                    $data['assigned_at'] = now();

                    return $data;
                }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('validateResponse')
                    ->label('Validar respuesta')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (EducationAssignment $record): bool => $record->isCompleted() && ! $record->isValidated())
                    ->schema([
                        Textarea::make('validation_notes')->label('Mensaje para el paciente o cuidador')->maxLength(2000),
                    ])
                    ->action(function (EducationAssignment $record, array $data): void {
                        $record->update([
                            'validated_by' => auth('web')->id(),
                            'validated_at' => now(),
                            'validation_notes' => filled($data['validation_notes'] ?? null)
                                ? trim($data['validation_notes'])
                                : null,
                        ]);

                        Notification::make()->success()->title('Respuesta educativa validada')->send();
                    }),
            ]);
    }
}
