<?php

namespace App\Filament\Pics\Resources\RecoveryGoals\RelationManagers;

use App\Models\GoalProgressReport;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProgressReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'progressReports';

    protected static ?string $title = 'Reportes de avance';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reported_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('reporter.name')->label('Reportado por')
                    ->getStateUsing(fn (GoalProgressReport $record): string => $record->reporter?->name ?? $record->reporter?->full_name ?? '—'),
                TextColumn::make('notes')->label('Notas')->wrap()->limit(100)->placeholder('—'),
                IconColumn::make('had_difficulty')->label('Dificultad')->boolean(),
                TextColumn::make('difficulty_reason')->label('Motivo')->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => GoalProgressReport::DIFFICULTY_REASONS[$state] ?? '—'),
                IconColumn::make('validated_at')->label('Validado')->boolean(state: fn (GoalProgressReport $record): bool => $record->isValidated()),
                TextColumn::make('validatedBy.name')->label('Validado por')->placeholder('—'),
            ])
            ->defaultSort('reported_at', 'desc')
            ->recordActions([
                Action::make('validate')
                    ->label('Validar')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (GoalProgressReport $record): bool => ! $record->isValidated())
                    ->schema([
                        Textarea::make('validation_notes')->label('Notas de validación'),
                    ])
                    ->action(function (GoalProgressReport $record, array $data): void {
                        $record->update([
                            'validated_by' => auth('web')->id(),
                            'validated_at' => now(),
                            'validation_notes' => $data['validation_notes'] ?? null,
                        ]);

                        Notification::make()->success()->title('Reporte validado')->send();
                    }),
            ]);
    }

    public function canCreate(): bool
    {
        return false;
    }

    public function canDelete($record): bool
    {
        return false;
    }
}
