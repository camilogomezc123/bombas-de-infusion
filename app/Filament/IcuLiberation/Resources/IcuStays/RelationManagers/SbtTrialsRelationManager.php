<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use App\Models\IcuSbtTrial;
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

class SbtTrialsRelationManager extends RelationManager
{
    protected static string $relationship = 'sbtTrials';

    protected static ?string $title = 'SBT (prueba de respiración espontánea)';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            DateTimePicker::make('screened_at')->label('Evaluación de elegibilidad')->seconds(false)->required(),
            Toggle::make('eligible')->label('Elegible')->live(),
            Select::make('exclusion_reason')->label('Razón de exclusión')->options(IcuSbtTrial::EXCLUSION_REASONS)
                ->visible(fn ($get) => ! $get('eligible')),
            TextInput::make('initial_parameters')->label('Parámetros iniciales')->visible(fn ($get) => (bool) $get('eligible')),
            TextInput::make('modality')->label('Modalidad')->visible(fn ($get) => (bool) $get('eligible')),
            DateTimePicker::make('started_at')->label('Inicio')->seconds(false)->visible(fn ($get) => (bool) $get('eligible')),
            TextInput::make('duration_minutes')->label('Duración (min)')->numeric()->visible(fn ($get) => (bool) $get('eligible')),
            Select::make('result')->label('Resultado')->options(IcuSbtTrial::RESULTS)->visible(fn ($get) => (bool) $get('eligible')),
            Toggle::make('extubation_assessed')->label('Extubación evaluada')->visible(fn ($get) => (bool) $get('eligible')),
            Textarea::make('failure_reason')->label('Causa de fracaso')->columnSpanFull(),
            Textarea::make('adverse_event')->label('Evento adverso')->columnSpanFull(),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('screened_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            IconColumn::make('eligible')->label('Elegible')->boolean(),
            TextColumn::make('result')->label('Resultado')->formatStateUsing(fn (?string $s) => IcuSbtTrial::RESULTS[$s] ?? '—')->badge(),
            IconColumn::make('extubation_assessed')->label('Extubación evaluada')->boolean(),
        ])->defaultSort('screened_at', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
