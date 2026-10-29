<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use App\Models\IcuSatTrial;
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

class SatTrialsRelationManager extends RelationManager
{
    protected static string $relationship = 'satTrials';

    protected static ?string $title = 'SAT (prueba de despertar espontáneo)';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            DateTimePicker::make('screened_at')->label('Evaluación de elegibilidad')->seconds(false)->required(),
            Toggle::make('eligible')->label('Elegible')->live(),
            Select::make('exclusion_reason')->label('Razón de exclusión')->options(IcuSatTrial::EXCLUSION_REASONS)
                ->visible(fn ($get) => ! $get('eligible')),
            DateTimePicker::make('started_at')->label('Inicio')->seconds(false)->visible(fn ($get) => (bool) $get('eligible')),
            Select::make('result')->label('Resultado')->options(IcuSatTrial::RESULTS)->visible(fn ($get) => (bool) $get('eligible')),
            Textarea::make('failure_reason')->label('Razón de fracaso')->columnSpanFull(),
            Textarea::make('subsequent_action')->label('Conducta posterior')->columnSpanFull(),
            Textarea::make('adverse_event')->label('Evento adverso')->columnSpanFull(),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('screened_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            IconColumn::make('eligible')->label('Elegible')->boolean(),
            TextColumn::make('result')->label('Resultado')->formatStateUsing(fn (?string $s) => IcuSatTrial::RESULTS[$s] ?? '—')->badge(),
        ])->defaultSort('screened_at', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
