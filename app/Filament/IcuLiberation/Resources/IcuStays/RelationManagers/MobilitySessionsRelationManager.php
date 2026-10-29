<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use App\Models\IcuMobilitySession;
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

class MobilitySessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'mobilitySessions';

    protected static ?string $title = 'Movilidad temprana';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            DateTimePicker::make('performed_at')->label('Fecha y hora')->seconds(false)->required(),
            Toggle::make('safety_screen_passed')->label('Tamizaje de seguridad aprobado'),
            TextInput::make('tool')->label('Herramienta utilizada'),
            Select::make('goal_level')->label('Meta de nivel')->options(IcuMobilitySession::LEVELS),
            Select::make('baseline_level')->label('Nivel basal')->options(IcuMobilitySession::LEVELS),
            Select::make('achieved_level')->label('Nivel alcanzado')->options(IcuMobilitySession::LEVELS),
            TextInput::make('duration_minutes')->label('Duración (min)')->numeric(),
            TextInput::make('distance_meters')->label('Distancia (m)')->numeric(),
            TextInput::make('participants')->label('Profesionales participantes'),
            TextInput::make('barrier')->label('Barrera'),
            Textarea::make('adverse_event')->label('Evento adverso')->columnSpanFull(),
            Textarea::make('next_plan')->label('Plan siguiente')->columnSpanFull(),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('performed_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            IconColumn::make('safety_screen_passed')->label('Seguro')->boolean(),
            TextColumn::make('achieved_level')->label('Nivel alcanzado')
                ->formatStateUsing(fn (?string $s) => IcuMobilitySession::LEVELS[$s] ?? '—')->badge(),
            TextColumn::make('barrier')->label('Barrera')->placeholder('—'),
        ])->defaultSort('performed_at', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
