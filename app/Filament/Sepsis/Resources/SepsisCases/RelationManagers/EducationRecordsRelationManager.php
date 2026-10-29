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

class EducationRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'educationRecords';

    protected static ?string $title = 'Educación al paciente y la familia';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TextInput::make('informed_person')->label('Persona informada')->required(),
            TextInput::make('relationship_to_patient')->label('Relación con el paciente'),
            DateTimePicker::make('occurred_at')->label('Fecha y hora')->seconds(false)->required(),
            Toggle::make('diagnosis_explained')->label('Diagnóstico explicado'),
            Toggle::make('treatment_explained')->label('Tratamiento explicado'),
            Toggle::make('risks_explained')->label('Riesgos explicados'),
            Toggle::make('procedures_explained')->label('Procedimientos explicados'),
            Toggle::make('prognosis_explained')->label('Pronóstico explicado'),
            Toggle::make('teach_back_done')->label('Teach-back realizado'),
            Toggle::make('discharge_readiness')->label('Preparado para egreso'),
            TextInput::make('material_provided')->label('Material entregado'),
            Textarea::make('goals_of_care')->label('Objetivos de atención')->columnSpanFull(),
            Textarea::make('preferences')->label('Preferencias del paciente')->columnSpanFull(),
            Textarea::make('comprehension_barriers')->label('Barreras de comprensión')->columnSpanFull(),
            Textarea::make('warning_signs')->label('Signos de alarma explicados')->columnSpanFull(),
            Textarea::make('post_sepsis_follow_up')->label('Seguimiento pos-sepsis')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                TextColumn::make('informed_person')->label('Persona informada'),
                IconColumn::make('teach_back_done')->label('Teach-back')->boolean(),
                IconColumn::make('discharge_readiness')->label('Listo para egreso')->boolean(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
