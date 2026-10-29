<?php

namespace App\Filament\Infarto\Resources\AcsCases\RelationManagers;

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

    protected static ?string $title = 'Seguimientos';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([Select::make('milestone')->label('Momento')->required()->options(['48_72h' => '48–72 horas', '7d' => '7 días', '14d' => '14 días', '30d' => '30 días', '4_8w' => '4–8 semanas', '3m' => '3 meses', '6m' => '6 meses', '12m' => '12 meses']), DatePicker::make('scheduled_on')->label('Programado')->required(), DateTimePicker::make('contacted_at')->label('Contacto'), Toggle::make('contact_achieved')->label('Contacto logrado'), Toggle::make('bleeding')->label('Sangrado'), Toggle::make('readmission')->label('Reingreso'), Toggle::make('reinfarction')->label('Reinfarto'), Toggle::make('rehabilitation_started')->label('Inició rehabilitación'), Toggle::make('mortality')->label('Mortalidad'), Textarea::make('symptoms')->label('Síntomas')->columnSpanFull(), Textarea::make('adherence_and_access')->label('Adherencia y acceso')->columnSpanFull(), Textarea::make('observations')->label('Observaciones')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('milestone')->label('Momento')->badge(), TextColumn::make('scheduled_on')->label('Programado')->date('d/m/Y'), IconColumn::make('contact_achieved')->label('Contacto')->boolean(), IconColumn::make('readmission')->label('Reingreso')->boolean(), IconColumn::make('rehabilitation_started')->label('Rehabilitación')->boolean()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
