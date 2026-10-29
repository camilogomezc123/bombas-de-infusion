<?php

namespace App\Filament\Infarto\Resources\AcsCases\RelationManagers;

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

class EcgRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'ecgRecords';

    protected static ?string $title = 'ECG seriados';

    public function form(Schema $s): Schema
    {
        return $s->columns(2)->components([DateTimePicker::make('performed_at')->label('Realizado')->required(), DateTimePicker::make('interpreted_at')->label('Interpretado'), TextInput::make('result')->label('Resultado'), Toggle::make('st_elevation')->label('Elevación ST'), Toggle::make('transmitted_prehospital')->label('Transmitido prehospitalario'), Textarea::make('interpretation')->label('Interpretación documentada')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('performed_at')->label('Realizado')->dateTime('d/m/Y H:i'), TextColumn::make('interpreted_at')->label('Interpretado')->dateTime('d/m/Y H:i'), TextColumn::make('result')->label('Resultado'), IconColumn::make('st_elevation')->label('ST ↑')->boolean()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
