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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AntimicrobialAdministrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'antimicrobialAdministrations';

    protected static ?string $title = 'Antimicrobianos';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TextInput::make('drug')->label('Antimicrobiano')->required(),
            TextInput::make('dose')->label('Dosis'),
            TextInput::make('route')->label('Vía'),
            DateTimePicker::make('ordered_at')->label('Hora de orden')->seconds(false),
            DateTimePicker::make('dispensed_at')->label('Hora de dispensación')->seconds(false),
            DateTimePicker::make('administered_at')->label('Hora de administración')->seconds(false),
            Toggle::make('renal_adjustment')->label('Ajuste renal'),
            Toggle::make('allergy_checked')->label('Alergias verificadas'),
            Toggle::make('proa_review')->label('Revisión PROA / infectología'),
            TextInput::make('adjustment_or_deescalation')->label('Ajuste o desescalamiento'),
            Textarea::make('resistance_factors')->label('Factores de resistencia')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('drug')->label('Antimicrobiano'),
                TextColumn::make('dose')->label('Dosis')->placeholder('—'),
                TextColumn::make('route')->label('Vía')->placeholder('—'),
                TextColumn::make('administered_at')->label('Administración')->dateTime('d/m/Y H:i')->placeholder('Pendiente'),
                TextColumn::make('adjustment_or_deescalation')->label('Ajuste')->placeholder('—'),
            ])
            ->defaultSort('administered_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
