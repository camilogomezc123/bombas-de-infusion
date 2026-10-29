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

class MedicationRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'medicationRecords';

    protected static ?string $title = 'Medicamentos (trazabilidad, no prescripción)';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([Select::make('medication_group')->label('Grupo')->required()->options(['aspirin' => 'Aspirina', 'p2y12' => 'P2Y12', 'anticoagulation' => 'Anticoagulación', 'statin' => 'Estatina alta intensidad', 'beta_blocker' => 'Betabloqueador', 'raas' => 'Sistema renina-angiotensina', 'mra' => 'Antagonista mineralocorticoide', 'other' => 'Otro']), TextInput::make('medication_name')->label('Medicamento documentado'), TextInput::make('documented_dose')->label('Dosis documentada'), DateTimePicker::make('ordered_at')->label('Orden'), DateTimePicker::make('administered_at')->label('Administración'), Toggle::make('indicated')->label('Indicado'), Toggle::make('at_discharge')->label('Plan al egreso'), Textarea::make('contraindication_or_omission_reason')->label('Contraindicación/omisión')->columnSpanFull(), Textarea::make('discharge_plan')->label('Plan documentado')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('medication_group')->label('Grupo')->badge(), TextColumn::make('medication_name')->label('Medicamento'), TextColumn::make('administered_at')->label('Administrado')->dateTime('d/m/Y H:i'), IconColumn::make('at_discharge')->label('Egreso')->boolean()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
