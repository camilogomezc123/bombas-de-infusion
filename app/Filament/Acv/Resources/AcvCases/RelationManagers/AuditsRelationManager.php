<?php

namespace App\Filament\Acv\Resources\AcvCases\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'Historial de modificaciones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('Sistema'),
                TextColumn::make('event')
                    ->label('Evento')
                    ->badge(),
                TextColumn::make('field')
                    ->label('Campo')
                    ->searchable(),
                TextColumn::make('old_value')
                    ->label('Valor anterior')
                    ->wrap()
                    ->limit(80),
                TextColumn::make('new_value')
                    ->label('Valor nuevo')
                    ->wrap()
                    ->limit(80),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
