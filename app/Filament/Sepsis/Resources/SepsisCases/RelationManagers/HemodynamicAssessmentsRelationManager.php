<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\RelationManagers;

use App\Models\SepsisHemodynamicAssessment;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HemodynamicAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'hemodynamicAssessments';

    protected static ?string $title = 'Reevaluaciones hemodinámicas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Reevaluación')->columnSpanFull()->tabs([
                Tab::make('Perfusión')->columns(3)->schema([
                    DateTimePicker::make('assessed_at')->label('Fecha y hora')->seconds(false)->required(),
                    TextInput::make('map')->label('PAM')->numeric(),
                    TextInput::make('diastolic_pressure')->label('Presión diastólica')->numeric(),
                    TextInput::make('heart_rate')->label('Frecuencia cardiaca')->numeric(),
                    TextInput::make('capillary_refill_seconds')->label('Llenado capilar (seg)')->numeric(),
                    TextInput::make('lactate')->label('Lactato')->numeric(),
                    TextInput::make('urine_output_ml')->label('Diuresis (ml)')->numeric(),
                    TextInput::make('fluid_intake_ml')->label('Líquidos administrados (ml)')->numeric()
                        ->helperText('Acumulado desde la última reevaluación, para vigilar sobrecarga de volumen.'),
                    TextInput::make('fluid_output_ml')->label('Líquidos eliminados (ml)')->numeric(),
                    TextInput::make('mental_status')->label('Estado mental'),
                    Toggle::make('cold_mottled_skin')->label('Piel fría o moteada'),
                    Toggle::make('congestion')->label('Congestión'),
                ]),
                Tab::make('Respuesta a volumen y fenotipo')->columns(3)->schema([
                    TextInput::make('volume_response_method')->label('Método de evaluación'),
                    TextInput::make('volume_response_result')->label('Resultado'),
                    Select::make('phenotype')->label('Fenotipo')->options(SepsisHemodynamicAssessment::PHENOTYPES),
                ]),
                Tab::make('Ecografía y soporte')->columns(3)->schema([
                    TextInput::make('vti')->label('VTI')->numeric(),
                    TextInput::make('lv_function')->label('Función ventricular izquierda'),
                    TextInput::make('rv_function')->label('Función ventricular derecha'),
                    TextInput::make('cvp')->label('PVC'),
                    Textarea::make('ultrasound_findings')->label('Hallazgos ecográficos')->columnSpanFull(),
                    Textarea::make('vasopressors')->label('Vasopresores')->columnSpanFull(),
                    Textarea::make('inotropes')->label('Inotrópicos')->columnSpanFull(),
                ]),
                Tab::make('Conducta')->schema([
                    Textarea::make('conduct')->label('Conducta')->columnSpanFull(),
                    DateTimePicker::make('next_assessment_at')->label('Próxima reevaluación')->seconds(false),
                ]),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assessed_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                TextColumn::make('map')->label('PAM')->placeholder('—'),
                TextColumn::make('lactate')->label('Lactato')->placeholder('—'),
                TextColumn::make('fluid_balance')->label('Balance')
                    ->state(fn (SepsisHemodynamicAssessment $record): string => $record->fluidBalanceMl() !== null
                        ? number_format($record->fluidBalanceMl(), 0, ',', '.').' ml'
                        : '—')
                    ->color(fn (SepsisHemodynamicAssessment $record): string => ($record->fluidBalanceMl() ?? 0) > 3000 ? 'danger' : 'gray'),
                TextColumn::make('phenotype')
                    ->label('Fenotipo')
                    ->formatStateUsing(fn (?string $state): string => $state ? (SepsisHemodynamicAssessment::PHENOTYPES[$state] ?? $state) : '—')
                    ->badge(),
                TextColumn::make('next_assessment_at')->label('Próxima reevaluación')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->defaultSort('assessed_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
