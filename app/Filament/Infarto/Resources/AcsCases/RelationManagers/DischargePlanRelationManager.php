<?php

namespace App\Filament\Infarto\Resources\AcsCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DischargePlanRelationManager extends RelationManager
{
    protected static string $relationship = 'dischargePlan';

    protected static ?string $title = 'Egreso y prevención secundaria';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([Textarea::make('final_diagnosis')->label('Diagnóstico final')->columnSpanFull(), Textarea::make('coronary_anatomy')->label('Anatomía coronaria')->columnSpanFull(), TextInput::make('ejection_fraction')->label('Fracción de eyección (%)')->numeric(), Toggle::make('medication_reconciliation')->label('Conciliación'), Toggle::make('dapt_plan')->label('Plan DAPT'), Toggle::make('high_intensity_statin')->label('Estatina alta intensidad'), Toggle::make('verbal_education')->label('Educación verbal'), Toggle::make('written_education')->label('Educación escrita'), Toggle::make('teach_back')->label('Teach-back'), Toggle::make('warning_signs')->label('Signos de alarma'), Toggle::make('smoking_plan')->label('Plan tabaquismo'), Toggle::make('cardiology_appointment')->label('Cita cardiología'), Toggle::make('primary_care_appointment')->label('Cita atención primaria'), Toggle::make('rehabilitation_referral')->label('Remisión rehabilitación'), Toggle::make('lipid_profile_plan')->label('Perfil lipídico 4–8 semanas'), Toggle::make('medication_access_verified')->label('Acceso a medicamentos'), Textarea::make('barriers_and_plan')->label('Barreras y plan')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('ejection_fraction')->label('FE')->suffix('%'), IconColumn::make('medication_reconciliation')->label('Conciliación')->boolean(), IconColumn::make('teach_back')->label('Teach-back')->boolean(), IconColumn::make('rehabilitation_referral')->label('Rehabilitación')->boolean()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
