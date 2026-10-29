<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\RelationManagers;

use App\Models\SepsisBundleTask;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BundleTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'bundleTasks';

    protected static ?string $title = 'Tareas del bundle';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TextInput::make('task_key')->label('Clave de la tarea')->required()->maxLength(60)
                ->helperText('Ej: lactate, antibiotic, cultures, crystalloids, source_control.'),
            TextInput::make('label')->label('Nombre visible')->required()->maxLength(150),
            Select::make('status')->label('Estado')->options(SepsisBundleTask::STATUSES)->default('pending')->required(),
            DateTimePicker::make('target_at')->label('Fecha objetivo')->seconds(false),
            DateTimePicker::make('done_at')->label('Fecha de ejecución')->seconds(false),
            Select::make('responsible_user_id')->label('Responsable')->relationship('responsible', 'name')->searchable()->preload(),
            TextInput::make('source')->label('Fuente'),
            Textarea::make('result')->label('Resultado')->columnSpanFull(),
            Textarea::make('justification')->label('Justificación (no indicado / contraindicado)')->columnSpanFull(),
            Toggle::make('escalated')->label('Escalado'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label('Tarea'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => SepsisBundleTask::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'done' => 'success',
                        'done_late', 'requires_audit' => 'warning',
                        'not_indicated', 'unavailable' => 'gray',
                        'contraindicated', 'omitted' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('target_at')->label('Objetivo')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('done_at')->label('Ejecutado')->dateTime('d/m/Y H:i')->placeholder('—'),
                IconColumn::make('escalated')->label('Escalado')->boolean(),
            ])
            ->defaultSort('target_at')
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('markDone')
                    ->label('Marcar cumplido')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (SepsisBundleTask $record): bool => ! in_array($record->status, ['done', 'done_late', 'not_indicated', 'contraindicated', 'omitted'], true))
                    ->action(function (SepsisBundleTask $record): void {
                        $now = now();
                        $record->update([
                            'status' => ($record->target_at && $now->gt($record->target_at)) ? 'done_late' : 'done',
                            'done_at' => $now,
                        ]);
                    }),
                EditAction::make(),
            ]);
    }
}
