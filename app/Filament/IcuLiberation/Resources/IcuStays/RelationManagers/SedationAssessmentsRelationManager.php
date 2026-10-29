<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SedationAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'sedationAssessments';

    protected static ?string $title = 'Analgesia y sedación';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            DateTimePicker::make('assessed_at')->label('Fecha y hora')->seconds(false)->required(),
            Select::make('goal_scale')->label('Escala')->options(['rass' => 'RASS', 'sas' => 'SAS']),
            TextInput::make('goal_value')->label('Objetivo'),
            TextInput::make('actual_value')->label('Nivel real'),
            TextInput::make('indication')->label('Indicación'),
            Toggle::make('nmb_used')->label('Bloqueo neuromuscular'),
            Textarea::make('analgesics')->label('Analgésicos')->columnSpanFull(),
            Textarea::make('sedatives')->label('Sedantes')->columnSpanFull(),
            Textarea::make('infusions')->label('Infusiones')->columnSpanFull(),
            Textarea::make('boluses')->label('Bolos')->columnSpanFull(),
            Textarea::make('deep_sedation_justification')->label('Justificación de sedación profunda')->columnSpanFull(),
            TextInput::make('withdrawal_risk')->label('Riesgo de abstinencia'),
            Textarea::make('withdrawal_assessment')->label('Evaluación de abstinencia')->columnSpanFull(),
            Textarea::make('adverse_event')->label('Evento adverso')->columnSpanFull(),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('assessed_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('goal_value')->label('Objetivo'),
            TextColumn::make('actual_value')->label('Real'),
            TextColumn::make('withdrawal_risk')->label('Riesgo de abstinencia')->placeholder('—'),
        ])->defaultSort('assessed_at', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
