<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

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

class FamilyEngagementsRelationManager extends RelationManager
{
    protected static string $relationship = 'familyEngagements';

    protected static ?string $title = 'Familia y humanización';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            DateTimePicker::make('occurred_at')->label('Fecha y hora')->seconds(false)->required(),
            TextInput::make('contact_name')->label('Familiar o cuidador principal'),
            TextInput::make('relationship')->label('Relación'),
            TextInput::make('communication_preferences')->label('Preferencias de comunicación'),
            TextInput::make('language')->label('Idioma'),
            TextInput::make('spiritual_support')->label('Apoyo espiritual'),
            Toggle::make('participated_in_round')->label('Participó en ronda'),
            Toggle::make('participated_in_mobility')->label('Participó en movilidad'),
            Toggle::make('participated_in_reorientation')->label('Participó en reorientación'),
            Toggle::make('education_provided')->label('Educación brindada'),
            Toggle::make('teach_back_confirmed')->label('Teach-back confirmado'),
            Toggle::make('meeting_held')->label('Reunión familiar realizada')->live(),
            TextInput::make('meeting_participants')->label('Participantes de la reunión')
                ->visible(fn ($get) => (bool) $get('meeting_held')),
            Textarea::make('meeting_objectives')->label('Objetivos de la reunión')->columnSpanFull()
                ->visible(fn ($get) => (bool) $get('meeting_held')),
            Textarea::make('information_provided')->label('Información entregada')->columnSpanFull(),
            TextInput::make('understanding_level')->label('Comprensión'),
            Textarea::make('questions')->label('Preguntas')->columnSpanFull(),
            Textarea::make('values_preferences')->label('Valores y preferencias')->columnSpanFull(),
            Textarea::make('decisions')->label('Decisiones')->columnSpanFull(),
            Textarea::make('commitments')->label('Compromisos')->columnSpanFull(),
            DateTimePicker::make('next_meeting_at')->label('Próxima reunión')->seconds(false),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('occurred_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('contact_name')->label('Familiar')->placeholder('—'),
            IconColumn::make('participated_in_round')->label('Ronda')->boolean(),
            IconColumn::make('meeting_held')->label('Reunión')->boolean(),
        ])->defaultSort('occurred_at', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
