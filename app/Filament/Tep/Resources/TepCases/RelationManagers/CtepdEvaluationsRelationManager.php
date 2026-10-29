<?php

namespace App\Filament\Tep\Resources\TepCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CtepdEvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'ctepdEvaluations';

    protected static ?string $title = 'Evaluación CTEPD / CTEPH';

    public function form(Schema $s): Schema
    {
        return $s->columns(2)->components([DatePicker::make('evaluated_on')->label('Evaluación'), Toggle::make('persistent_symptoms')->label('Síntomas persistentes'), TextInput::make('study')->label('Estudio'), TextInput::make('result')->label('Resultado'), Toggle::make('ctepd_suspected')->label('CTEPD sospechada'), Toggle::make('cteph_suspected')->label('CTEPH sospechada'), Textarea::make('referral_plan')->label('Plan de remisión')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('evaluated_on')->label('Fecha')->date('d/m/Y'), TextColumn::make('study')->label('Estudio'), IconColumn::make('ctepd_suspected')->label('CTEPD')->boolean(), IconColumn::make('cteph_suspected')->label('CTEPH')->boolean()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
