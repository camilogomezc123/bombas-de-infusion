<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use App\Models\IcuDeliriumAssessment;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliriumAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'deliriumAssessments';

    protected static ?string $title = 'Delirium y cognición';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            DateTimePicker::make('assessed_at')->label('Fecha y hora')->seconds(false)->required(),
            Select::make('tool')->label('Herramienta')->options(IcuDeliriumAssessment::TOOLS),
            Toggle::make('assessable')->label('Evaluable')->live(),
            TextInput::make('coma_state')->label('Estado de coma')->visible(fn ($get) => ! $get('assessable')),
            Select::make('result')->label('Resultado')->options(IcuDeliriumAssessment::RESULTS)
                ->visible(fn ($get) => (bool) $get('assessable')),
            TextInput::make('subtype')->label('Subtipo')->visible(fn ($get) => (bool) $get('assessable')),
            Textarea::make('precipitating_factors')->label('Factores precipitantes')->columnSpanFull(),
            Textarea::make('associated_medications')->label('Medicamentos asociados')->columnSpanFull(),
            CheckboxList::make('interventions')->label('Intervenciones no farmacológicas')->columns(3)->columnSpanFull()
                ->options([
                    'reorientation' => 'Reorientación', 'clock_calendar' => 'Reloj y calendario', 'daylight' => 'Luz diurna',
                    'vision_correction' => 'Corrección visual', 'hearing_aids' => 'Audífonos', 'mobility' => 'Movilidad',
                    'sleep' => 'Sueño', 'device_removal' => 'Retiro de dispositivos', 'restraint_reduction' => 'Reducción de restricciones',
                    'hydration' => 'Hidratación', 'family_participation' => 'Participación familiar', 'cognitive_activity' => 'Actividad cognitiva',
                ]),
            DateTimePicker::make('reassessment_at')->label('Revaluación')->seconds(false),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('assessed_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('tool')->label('Herramienta')->formatStateUsing(fn (?string $s) => IcuDeliriumAssessment::TOOLS[$s] ?? '—')->badge(),
            TextColumn::make('result')->label('Resultado')->formatStateUsing(fn (?string $s) => IcuDeliriumAssessment::RESULTS[$s] ?? '—')
                ->badge()->color(fn (?string $s) => $s === 'positive' ? 'danger' : 'gray'),
        ])->defaultSort('assessed_at', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
