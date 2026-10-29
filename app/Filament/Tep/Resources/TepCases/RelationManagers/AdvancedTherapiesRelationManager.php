<?php

namespace App\Filament\Tep\Resources\TepCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdvancedTherapiesRelationManager extends RelationManager
{
    protected static string $relationship = 'advancedTherapies';

    protected static ?string $title = 'Terapias avanzadas';

    public function form(Schema $s): Schema
    {
        return $s->columns(2)->components([Select::make('therapy_type')->label('Terapia')->required()->options(['systemic_thrombolysis' => 'Trombólisis sistémica', 'catheter' => 'Terapia por catéter', 'surgery' => 'Embolectomía quirúrgica', 'ecmo' => 'ECMO', 'other' => 'Otra']), DateTimePicker::make('decided_at')->label('Decisión'), DateTimePicker::make('started_at')->label('Inicio'), Toggle::make('completed')->label('Completada'), Textarea::make('indication')->label('Indicación')->columnSpanFull(), Textarea::make('contraindications')->label('Contraindicaciones')->columnSpanFull(), Textarea::make('outcome')->label('Resultado')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('therapy_type')->label('Terapia')->badge(), TextColumn::make('started_at')->label('Inicio')->dateTime('d/m/Y H:i'), IconColumn::make('completed')->label('Completada')->boolean()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
