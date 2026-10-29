<?php

namespace App\Filament\Infarto\Resources\AcsCases\RelationManagers;

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

class PciProceduresRelationManager extends RelationManager
{
    protected static string $relationship = 'pciProcedures';

    protected static ?string $title = 'Hemodinamia y PCI';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([DateTimePicker::make('started_at')->label('Inicio'), DateTimePicker::make('first_device_at')->label('Primer dispositivo'), DateTimePicker::make('completed_at')->label('Fin'), TextInput::make('operator')->label('Operador'), Select::make('vascular_access')->label('Acceso')->options(['radial' => 'Radial', 'femoral' => 'Femoral', 'other' => 'Otro']), TextInput::make('culprit_artery')->label('Arteria culpable'), TextInput::make('initial_flow')->label('Flujo inicial'), TextInput::make('final_flow')->label('Flujo final'), TextInput::make('intervention_type')->label('Intervención'), Toggle::make('multivessel_disease')->label('Enfermedad multivaso'), Toggle::make('intracoronary_imaging')->label('Imagen intracoronaria'), Toggle::make('successful')->label('Procedimiento exitoso'), Textarea::make('complications')->label('Complicaciones')->columnSpanFull(), Textarea::make('complete_revascularization_plan')->label('Plan de revascularización completa')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('started_at')->label('Inicio')->dateTime('d/m/Y H:i'), TextColumn::make('vascular_access')->label('Acceso')->badge(), TextColumn::make('culprit_artery')->label('Arteria'), TextColumn::make('final_flow')->label('Flujo final'), IconColumn::make('successful')->label('Éxito')->boolean()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
