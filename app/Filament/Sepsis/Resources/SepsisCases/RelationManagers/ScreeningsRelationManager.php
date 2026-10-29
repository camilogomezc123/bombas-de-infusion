<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\RelationManagers;

use App\Models\SepsisScreening;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScreeningsRelationManager extends RelationManager
{
    protected static string $relationship = 'screenings';

    protected static ?string $title = 'Tamizaje';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            DateTimePicker::make('occurred_at')->label('Fecha y hora')->seconds(false)->required(),
            TextInput::make('service')->label('Servicio'),
            TextInput::make('clinician_name')->label('Profesional'),
            Toggle::make('suspected_infection')->label('Sospecha de infección'),
            TextInput::make('probable_focus')->label('Foco probable'),
            Select::make('perfusion_status')->label('Perfusión')->options([
                'normal' => 'Normal', 'altered' => 'Alterada',
            ]),
            TextInput::make('mental_status')->label('Estado mental'),
            TextInput::make('urine_output_ml')->label('Diuresis (ml)')->numeric(),
            TextInput::make('news2_score')->label('NEWS2')->numeric()->maxValue(20),
            TextInput::make('sofa_score')->label('SOFA (total)')->numeric()->maxValue(24)
                ->helperText('Puede digitarse directamente o calcularse desde los 6 componentes abajo.'),
            Select::make('result')->label('Resultado')->options([
                'positive' => 'Positivo', 'negative' => 'Negativo', 'inconclusive' => 'No concluyente',
            ]),

            Section::make('Componentes del SOFA (opcional)')
                ->columns(3)
                ->columnSpanFull()
                ->collapsed()
                ->schema([
                    ...collect(SepsisScreening::SOFA_COMPONENTS)->map(
                        fn (string $label, string $field) => TextInput::make($field)->label($label)->numeric()->minValue(0)->maxValue(4),
                    )->values()->all(),
                    Placeholder::make('sofa_components_note')
                        ->label('')
                        ->content('Cada componente se puntúa de 0 a 4. Registrarlos por separado permite ver qué órgano está fallando, no solo el total.')
                        ->columnSpanFull(),
                ]),

            Textarea::make('conduct')->label('Conducta')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                TextColumn::make('service')->label('Servicio')->placeholder('—'),
                TextColumn::make('news2_score')->label('NEWS2')->placeholder('—'),
                IconColumn::make('suspected_infection')->label('Sospecha infección')->boolean(),
                TextColumn::make('result')->label('Resultado')->badge()->placeholder('—'),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
