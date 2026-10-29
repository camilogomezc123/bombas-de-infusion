<?php

namespace App\Filament\Tep\Resources\TepCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PertActivationsRelationManager extends RelationManager
{
    protected static string $relationship = 'pertActivations';

    protected static ?string $title = 'PERT';

    public function form(Schema $s): Schema
    {
        return $s->columns(2)->components([DateTimePicker::make('requested_at')->label('Solicitud'), DateTimePicker::make('activated_at')->label('Activación'), DateTimePicker::make('decision_at')->label('Decisión'), TextInput::make('decision')->label('Decisión documentada'), Textarea::make('rationale')->label('Justificación')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('activated_at')->label('Activación')->dateTime('d/m/Y H:i'), TextColumn::make('decision')->label('Decisión'), TextColumn::make('decision_at')->label('Hora de decisión')->dateTime('d/m/Y H:i')])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
