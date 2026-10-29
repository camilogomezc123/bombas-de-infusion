<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PhysicalRestraintsRelationManager extends RelationManager
{
    protected static string $relationship = 'physicalRestraints';

    protected static ?string $title = 'Restricciones físicas';

    public function form(Schema $s): Schema
    {
        return $s->columns(2)->components([
            TextInput::make('restraint_type')->label('Tipo de restricción')->required(),
            DateTimePicker::make('started_at')->label('Inicio')->seconds(false)->required(),
            Textarea::make('indication')->label('Indicación')->columnSpanFull(),
            Textarea::make('alternatives_tried')->label('Alternativas intentadas')->columnSpanFull(),
            DateTimePicker::make('reassessed_at')->label('Revaluación')->seconds(false),
            DateTimePicker::make('removed_at')->label('Fecha de retiro')->seconds(false),
            Textarea::make('related_event')->label('Eventos relacionados')->columnSpanFull(),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('restraint_type')->label('Tipo'),
            TextColumn::make('started_at')->label('Inicio')->dateTime('d/m/Y H:i')->sortable(),
            IconColumn::make('removed_at')->label('Retirada')->boolean(fn ($state) => filled($state)),
        ])->defaultSort('started_at', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
