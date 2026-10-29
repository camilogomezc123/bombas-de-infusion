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

class CareTransitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'careTransitions';

    protected static ?string $title = 'Continuidad y traslados';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TextInput::make('origin_service')->label('Servicio de origen')->required(),
            TextInput::make('destination_service')->label('Servicio destino')->required(),
            DateTimePicker::make('transitioned_at')->label('Fecha y hora')->seconds(false)->required(),
            TextInput::make('handing_clinician')->label('Profesional que entrega'),
            TextInput::make('receiving_clinician')->label('Profesional que recibe'),
            Toggle::make('bundle_pending')->label('Bundle pendiente'),
            Toggle::make('source_control_pending')->label('Control del foco pendiente'),
            Toggle::make('icu_needed')->label('Necesidad de UCI'),
            Toggle::make('discharge')->label('Es egreso'),
            Textarea::make('clinical_status')->label('Estado clínico')->columnSpanFull(),
            Textarea::make('pending_items')->label('Pendientes')->columnSpanFull(),
            Textarea::make('active_antimicrobials')->label('Antimicrobianos activos')->columnSpanFull(),
            Textarea::make('post_sepsis_plan')->label('Plan pos-sepsis')->columnSpanFull()
                ->visible(fn ($get): bool => (bool) $get('discharge')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transitioned_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                TextColumn::make('origin_service')->label('Origen'),
                TextColumn::make('destination_service')->label('Destino'),
                IconColumn::make('bundle_pending')->label('Bundle pendiente')->boolean(),
                IconColumn::make('discharge')->label('Egreso')->boolean(),
            ])
            ->defaultSort('transitioned_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
