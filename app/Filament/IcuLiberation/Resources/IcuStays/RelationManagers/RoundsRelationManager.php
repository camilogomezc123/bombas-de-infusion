<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoundsRelationManager extends RelationManager
{
    protected static string $relationship = 'rounds';

    protected static ?string $title = 'Rondas ICU Liberation';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            DatePicker::make('round_date')->label('Fecha')->required(),
            TextInput::make('rass_sas_goal')->label('RASS/SAS objetivo'),
            TextInput::make('rass_sas_actual')->label('RASS/SAS actual'),
            TextInput::make('cam_icdsc_result')->label('CAM-ICU/ICDSC'),
            Textarea::make('goals_of_day')->label('Metas del día')->columnSpanFull(),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('round_date')->label('Fecha')->date('d/m/Y')->sortable(),
            TextColumn::make('rass_sas_goal')->label('RASS/SAS objetivo'),
            TextColumn::make('rass_sas_actual')->label('RASS/SAS actual'),
            TextColumn::make('cam_icdsc_result')->label('CAM-ICU/ICDSC'),
        ])->defaultSort('round_date', 'desc')->recordActions([EditAction::make()]);
    }
}
