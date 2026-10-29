<?php

namespace App\Filament\Infarto\Resources\AcsCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ComplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'complications';

    protected static ?string $title = 'Complicaciones';

    public function form(Schema $s): Schema
    {
        return $s->columns(2)->components([Select::make('type')->label('Tipo')->required()->options(['cardiogenic_shock' => 'Choque cardiogénico', 'cardiac_arrest' => 'Paro cardiaco', 'ventricular_arrhythmia' => 'Arritmia ventricular', 'heart_failure' => 'Insuficiencia cardiaca', 'mechanical' => 'Complicación mecánica', 'major_bleeding' => 'Sangrado mayor', 'stroke' => 'ACV', 'aki' => 'Lesión renal aguda', 'reinfarction' => 'Reinfarto', 'death' => 'Muerte', 'other' => 'Otra']), DateTimePicker::make('recognized_at')->label('Reconocimiento'), Select::make('severity')->label('Severidad')->options(['mild' => 'Leve', 'moderate' => 'Moderada', 'severe' => 'Grave', 'fatal' => 'Fatal']), Select::make('outcome')->label('Resultado')->options(['resolved' => 'Resuelta', 'ongoing' => 'En curso', 'sequelae' => 'Secuela', 'death' => 'Muerte']), Textarea::make('management')->label('Manejo documentado')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('type')->label('Complicación')->badge(), TextColumn::make('recognized_at')->label('Reconocida')->dateTime('d/m/Y H:i'), TextColumn::make('severity')->label('Severidad')->badge(), TextColumn::make('outcome')->label('Resultado')])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
