<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use App\Models\IcuPainAssessment;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PainAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'painAssessments';

    protected static ?string $title = 'Dolor';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            DateTimePicker::make('assessed_at')->label('Fecha y hora')->seconds(false)->required(),
            Select::make('scale')->label('Escala')->options(IcuPainAssessment::SCALES),
            TextInput::make('score')->label('Puntaje'),
            Toggle::make('self_report_capable')->label('Capaz de autorreporte'),
            TextInput::make('location')->label('Localización'),
            TextInput::make('pain_type')->label('Tipo'),
            TextInput::make('related_procedure')->label('Procedimiento asociado'),
            Textarea::make('intervention')->label('Intervención')->columnSpanFull(),
            Textarea::make('non_pharmacologic_intervention')->label('Intervención no farmacológica')->columnSpanFull(),
            DateTimePicker::make('reassessed_at')->label('Revaluación')->seconds(false),
            TextInput::make('score_after')->label('Puntaje tras intervención'),
            Textarea::make('adverse_event')->label('Evento adverso')->columnSpanFull(),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('assessed_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('scale')->label('Escala')->formatStateUsing(fn (?string $s) => IcuPainAssessment::SCALES[$s] ?? $s)->badge(),
            TextColumn::make('score')->label('Puntaje'),
            TextColumn::make('score_after')->label('Tras intervención')->placeholder('—'),
        ])->defaultSort('assessed_at', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
