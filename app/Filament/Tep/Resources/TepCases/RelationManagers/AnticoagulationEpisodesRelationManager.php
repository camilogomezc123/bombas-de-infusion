<?php

namespace App\Filament\Tep\Resources\TepCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnticoagulationEpisodesRelationManager extends RelationManager
{
    protected static string $relationship = 'anticoagulationEpisodes';

    protected static ?string $title = 'Anticoagulación';

    public function form(Schema $s): Schema
    {
        return $s->columns(2)->components([TextInput::make('medication')->label('Medicamento'), Select::make('phase')->options(['initial' => 'Inicial', 'maintenance' => 'Mantenimiento', 'discharge' => 'Egreso']), DateTimePicker::make('ordered_at')->label('Orden'), DateTimePicker::make('started_at')->label('Inicio'), TextInput::make('documented_dose')->label('Dosis documentada'), Textarea::make('contraindication_or_omission_reason')->label('Contraindicación/omisión')->columnSpanFull(), Textarea::make('transition_plan')->label('Plan de transición')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('medication')->label('Medicamento'), TextColumn::make('phase')->label('Fase')->badge(), TextColumn::make('started_at')->label('Inicio')->dateTime('d/m/Y H:i')])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
