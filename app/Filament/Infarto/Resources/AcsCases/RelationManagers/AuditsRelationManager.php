<?php

namespace App\Filament\Infarto\Resources\AcsCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'Auditoría clínica';

    public function form(Schema $s): Schema
    {
        return $s->columns(2)->components([Select::make('auditor_id')->label('Auditor')->relationship('auditor', 'name')->searchable()->preload(), Select::make('priority_reason')->label('Priorización')->options(['stemi' => 'STEMI', 'death' => 'Mortalidad', 'late_ecg' => 'ECG tardío', 'late_reperfusion' => 'Reperfusión tardía', 'no_reperfusion' => 'Sin reperfusión', 'shock' => 'Choque', 'major_bleeding' => 'Sangrado mayor', 'readmission' => 'Reingreso', 'other' => 'Otro']), Select::make('status')->label('Estado')->options(['pending' => 'Pendiente', 'in_progress' => 'En análisis', 'completed' => 'Finalizada'])->default('pending'), DateTimePicker::make('completed_at')->label('Finalizada'), TagsInput::make('criteria_met')->label('Criterios cumplidos')->columnSpanFull(), TagsInput::make('criteria_not_met')->label('Criterios no cumplidos')->columnSpanFull(), Textarea::make('barriers')->label('Barreras')->columnSpanFull(), Textarea::make('probable_cause')->label('Causa probable')->columnSpanFull(), Textarea::make('conclusion')->label('Conclusión')->columnSpanFull(), Textarea::make('recommendation')->label('Recomendación')->columnSpanFull(), Toggle::make('requires_five_whys')->label('Requiere 5 Porqués'), Toggle::make('requires_phva')->label('Requiere PHVA')]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('priority_reason')->label('Motivo')->badge(), TextColumn::make('auditor.name')->label('Auditor'), TextColumn::make('status')->label('Estado')->badge(), IconColumn::make('requires_five_whys')->label('5 Porqués')->boolean(), IconColumn::make('requires_phva')->label('PHVA')->boolean()])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
