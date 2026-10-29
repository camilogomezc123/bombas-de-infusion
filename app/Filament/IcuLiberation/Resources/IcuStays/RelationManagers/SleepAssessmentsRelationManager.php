<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SleepAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'sleepAssessments';

    protected static ?string $title = 'Sueño y ambiente';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            DateTimePicker::make('assessed_at')->label('Fecha y hora')->seconds(false)->required(),
            TextInput::make('usual_habits')->label('Hábitos habituales'),
            TextInput::make('subjective_quality')->label('Calidad subjetiva'),
            Toggle::make('nocturnal_pain')->label('Dolor nocturno'),
            Toggle::make('anxiety')->label('Ansiedad'),
            TextInput::make('light_exposure')->label('Exposición a luz'),
            TextInput::make('noise_level')->label('Nivel de ruido'),
            TextInput::make('interruptions_count')->label('N.º de interrupciones')->numeric(),
            TextInput::make('night_medication')->label('Medicación nocturna'),
            Toggle::make('ventilation_interference')->label('Interferencia de la ventilación'),
            Toggle::make('procedures_at_night')->label('Procedimientos nocturnos'),
            TextInput::make('day_night_orientation')->label('Orientación día-noche'),
            CheckboxList::make('interventions')->label('Intervenciones')->columns(3)->columnSpanFull()->options([
                'dim_lighting' => 'Disminuir iluminación nocturna', 'daylight' => 'Favorecer luz diurna',
                'cluster_care' => 'Agrupar cuidados', 'reduce_noise' => 'Reducir ruido', 'review_alarms' => 'Revisar alarmas',
                'eye_mask' => 'Antifaz', 'earplugs' => 'Tapones', 'routines' => 'Rutinas', 'music' => 'Música o relajación',
                'avoid_interruptions' => 'Evitar interrupciones no esenciales', 'respect_preferences' => 'Respetar preferencias',
            ]),
            Textarea::make('barriers')->label('Barreras')->columnSpanFull(),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('assessed_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('subjective_quality')->label('Calidad subjetiva')->placeholder('—'),
            TextColumn::make('interruptions_count')->label('Interrupciones')->placeholder('—'),
        ])->defaultSort('assessed_at', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
