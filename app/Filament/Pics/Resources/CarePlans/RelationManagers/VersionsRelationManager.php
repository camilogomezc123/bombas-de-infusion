<?php

namespace App\Filament\Pics\Resources\CarePlans\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Historial de versiones del plan: solo lectura. Se crea automáticamente
 * (CarePlanObserver) cuando cambia el objetivo, los criterios de egreso o el plan por
 * disciplina — nunca se edita ni se borra manualmente.
 */
class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Historial de versiones';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version')->label('Versión')->badge(),
                TextColumn::make('effective_from')->label('Vigente desde')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('effective_until')->label('Vigente hasta')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('general_objective')->label('Objetivo general')->wrap()->limit(60)->placeholder('—'),
                TextColumn::make('discharge_criteria')->label('Criterios de egreso')->wrap()->limit(60)->placeholder('—'),
                TextColumn::make('changedBy.name')->label('Modificado por')->placeholder('—'),
            ])
            ->defaultSort('version', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
