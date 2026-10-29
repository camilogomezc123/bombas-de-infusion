<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CulturesRelationManager extends RelationManager
{
    protected static string $relationship = 'cultures';

    protected static ?string $title = 'Cultivos';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('site')->label('Sitio de cultivo')->required(),
            DateTimePicker::make('taken_at')->label('Fecha de toma')->seconds(false),
            TextInput::make('microorganism')->label('Microorganismo'),
            Toggle::make('contamination')->label('Contaminación'),
            Textarea::make('susceptibility')->label('Susceptibilidad')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site')->label('Sitio'),
                TextColumn::make('taken_at')->label('Toma')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('microorganism')->label('Microorganismo')->placeholder('Pendiente'),
                IconColumn::make('contamination')->label('Contaminación')->boolean(),
            ])
            ->defaultSort('taken_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
