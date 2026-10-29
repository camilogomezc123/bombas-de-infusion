<?php

namespace App\Filament\Infarto\Resources\AcsCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TroponinRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'troponinRecords';

    protected static ?string $title = 'Troponinas';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([DateTimePicker::make('collected_at')->label('Toma')->required(), DateTimePicker::make('resulted_at')->label('Resultado'), TextInput::make('value')->label('Valor')->numeric(), TextInput::make('unit')->label('Unidad'), Toggle::make('high_sensitivity')->label('Alta sensibilidad')->default(true), Select::make('interpretation')->label('Interpretación')->options(['negative' => 'Negativa', 'positive' => 'Positiva', 'dynamic_change' => 'Cambio dinámico', 'indeterminate' => 'Indeterminada'])]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('collected_at')->label('Toma')->dateTime('d/m/Y H:i'), TextColumn::make('resulted_at')->label('Resultado')->dateTime('d/m/Y H:i'), TextColumn::make('value')->label('Valor'), IconColumn::make('high_sensitivity')->label('hs')->boolean(), TextColumn::make('interpretation')->label('Interpretación')->badge()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
