<?php

namespace App\Filament\Tep\Resources\TepCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FollowupsRelationManager extends RelationManager
{
    protected static string $relationship = 'followups';

    protected static ?string $title = 'Seguimiento post-TEP';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([Select::make('milestone')->label('Momento')->required()->options(['7d' => '7 días', '30d' => '30 días', '3m' => '3 meses', '6m' => '6 meses', '12m' => '12 meses']), DatePicker::make('scheduled_on')->label('Programado'), DateTimePicker::make('contacted_at')->label('Contacto'), Toggle::make('contact_achieved')->label('Contacto logrado'), Toggle::make('persistent_dyspnea')->label('Disnea persistente'), Toggle::make('bleeding')->label('Sangrado'), Toggle::make('recurrence')->label('Recurrencia'), Toggle::make('readmission')->label('Reingreso'), Toggle::make('mortality')->label('Mortalidad'), Textarea::make('adherence_and_access')->label('Adherencia y acceso')->columnSpanFull(), Textarea::make('observations')->label('Observaciones')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('milestone')->label('Momento')->badge(), TextColumn::make('scheduled_on')->label('Programado')->date('d/m/Y'), IconColumn::make('contact_achieved')->label('Contacto')->boolean(), IconColumn::make('persistent_dyspnea')->label('Síntomas')->boolean(), IconColumn::make('recurrence')->label('Recurrencia')->boolean()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
