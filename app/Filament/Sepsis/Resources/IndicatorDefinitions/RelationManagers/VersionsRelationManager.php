<?php

namespace App\Filament\Sepsis\Resources\IndicatorDefinitions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Historial de versiones de la ficha técnica: solo lectura. Se crea automáticamente
 * (IndicatorDefinitionObserver) cuando cambia numerador/denominador/exclusiones/fórmula/
 * método de validación/meta — nunca se edita ni se borra manualmente.
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
                TextColumn::make('numerator')->label('Numerador')->wrap()->limit(60)->placeholder('—'),
                TextColumn::make('denominator')->label('Denominador')->wrap()->limit(60)->placeholder('—'),
                TextColumn::make('exclusion_criteria')->label('Exclusiones')->wrap()->limit(60)->placeholder('—'),
                TextColumn::make('target_value')->label('Meta')->placeholder('—'),
                TextColumn::make('changedBy.name')->label('Modificado por')->placeholder('—'),
            ])
            ->defaultSort('version', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
