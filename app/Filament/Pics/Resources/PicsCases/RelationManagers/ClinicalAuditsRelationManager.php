<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClinicalAuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    protected static ?string $title = 'Historial de cambios';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s', timezone: 'America/Bogota'),
                TextColumn::make('user.name')->label('Usuario')->placeholder('Sistema'),
                TextColumn::make('event')->label('Evento')->badge(),
                TextColumn::make('field')->label('Campo')->placeholder('Registro'),
                TextColumn::make('old_value')->label('Valor anterior')->wrap()->limit(100),
                TextColumn::make('new_value')->label('Valor nuevo')->wrap()->limit(100),
                TextColumn::make('change_reason')->label('Motivo')->wrap()->placeholder('Sin motivo registrado'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
