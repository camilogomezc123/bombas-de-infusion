<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use App\Models\IcuPicsFollowup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PicsFollowupsRelationManager extends RelationManager
{
    protected static string $relationship = 'picsFollowups';

    protected static ?string $title = 'Recuperación post-UCI (PICS)';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            Select::make('checkpoint')->label('Momento')->options(IcuPicsFollowup::CHECKPOINTS)->required(),
            TextInput::make('contact_method')->label('Medio de contacto'),
            Toggle::make('contact_achieved')->label('Contacto logrado'),
            DateTimePicker::make('followed_up_at')->label('Fecha de seguimiento')->seconds(false),
            TextInput::make('functional_capacity')->label('Capacidad funcional'),
            TextInput::make('mobility')->label('Movilidad'),
            TextInput::make('strength')->label('Fuerza'),
            TextInput::make('fatigue')->label('Fatiga'),
            TextInput::make('pain')->label('Dolor'),
            TextInput::make('sleep_quality')->label('Sueño'),
            TextInput::make('cognition_tool')->label('Herramienta cognición'),
            TextInput::make('cognition_result')->label('Resultado cognición'),
            TextInput::make('anxiety_tool')->label('Herramienta ansiedad'),
            TextInput::make('anxiety_result')->label('Resultado ansiedad'),
            TextInput::make('depression_tool')->label('Herramienta depresión'),
            TextInput::make('depression_result')->label('Resultado depresión'),
            TextInput::make('ptsd_tool')->label('Herramienta estrés postraumático'),
            TextInput::make('ptsd_result')->label('Resultado estrés postraumático'),
            Toggle::make('readmission')->label('Reingreso'),
            Toggle::make('return_to_work')->label('Retorno laboral'),
            TextInput::make('quality_of_life_tool')->label('Herramienta calidad de vida'),
            TextInput::make('quality_of_life_result')->label('Resultado calidad de vida'),
            TextInput::make('caregiver_burden_tool')->label('Herramienta carga del cuidador'),
            TextInput::make('caregiver_burden_result')->label('Resultado carga del cuidador'),
            Textarea::make('medications_review')->label('Revisión de medicamentos')->columnSpanFull(),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('checkpoint')->label('Momento')->formatStateUsing(fn (string $s) => IcuPicsFollowup::CHECKPOINTS[$s] ?? $s)->badge(),
            IconColumn::make('contact_achieved')->label('Contacto')->boolean(),
            TextColumn::make('followed_up_at')->label('Fecha')->dateTime('d/m/Y H:i')->placeholder('—'),
            IconColumn::make('readmission')->label('Reingreso')->boolean(),
        ])->defaultSort('id', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
