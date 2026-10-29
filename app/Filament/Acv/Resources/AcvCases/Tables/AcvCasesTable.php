<?php

namespace App\Filament\Acv\Resources\AcvCases\Tables;

use App\Filament\Acv\Resources\AcvCases\AcvCaseResource;
use App\Models\AcvCase;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AcvCasesTable
{
    /**
     * Columnas reutilizables por el recurso y por los listados Activos/Egresados/Analizados.
     *
     * @return array<int, Column>
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('case_number')->label('Caso')->searchable()->sortable()->copyable(),
            TextColumn::make('patient.full_name')
                ->label('Paciente')
                ->description(fn ($record): string => $record->patient->identification)
                ->searchable(['full_name', 'identification'])
                ->sortable(),
            TextColumn::make('is_recurrence')
                ->label('Recurrencia')
                ->badge()
                ->formatStateUsing(fn ($state): string => $state ? 'Recurrente' : '—')
                ->color(fn ($state): string => $state ? 'warning' : 'gray'),
            TextColumn::make('admission_number')->label('N.º ingreso')->searchable()->toggleable(),
            TextColumn::make('status')
                ->label('Estado')
                ->formatStateUsing(fn ($state): string => $state?->label() ?? 'Sin estado')
                ->badge()
                ->color(fn ($state): string => match ($state?->value) {
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    'pending' => 'warning',
                    'hospitalized' => 'info',
                    default => 'primary',
                }),
            TextColumn::make('stroke_type')->label('Tipo')->badge()->searchable(),
            TextColumn::make('arrival_at')->label('Ingreso / hora puerta')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('discharged_at')->label('Egreso')->dateTime('d/m/Y H:i')->sortable()->placeholder('—'),
            TextColumn::make('month')->label('Mes')->placeholder('—')->sortable()->toggleable(),
            TextColumn::make('assignedAuditor.name')
                ->label('Auditor')
                ->placeholder('Sin asignar')
                ->searchable()
                ->toggleable(),
            TextColumn::make('eapb')
                ->label('EAPB')
                ->placeholder('—')
                ->searchable()
                ->toggleable(),
        ];
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'imported' => 'Importado',
                        'hospitalized' => 'Hospitalizado',
                        'assigned' => 'Asignado',
                        'in_review' => 'En revisión',
                        'pending' => 'Pendiente',
                        'completed' => 'Finalizado',
                        'cancelled' => 'Anulado',
                    ]),
                SelectFilter::make('stroke_type')
                    ->label('Tipo de ACV')
                    ->options(fn (): array => AcvCase::query()
                        ->whereNotNull('stroke_type')
                        ->distinct()
                        ->orderBy('stroke_type')
                        ->pluck('stroke_type', 'stroke_type')
                        ->all()),
                SelectFilter::make('assigned_auditor_id')
                    ->label('Auditor')
                    ->relationship('assignedAuditor', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record): bool => AcvCaseResource::canEdit($record)),
            ])
            ->defaultSort('case_sequence', 'desc')
            ->striped();
    }
}
