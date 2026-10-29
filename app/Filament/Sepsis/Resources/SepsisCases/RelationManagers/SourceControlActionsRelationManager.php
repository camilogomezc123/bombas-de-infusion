<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SourceControlActionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sourceControlActions';

    protected static ?string $title = 'Control del foco';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TextInput::make('specialty')->label('Especialidad'),
            DateTimePicker::make('requested_at')->label('Solicitud')->seconds(false),
            DateTimePicker::make('assessed_at')->label('Valoración')->seconds(false),
            TextInput::make('decision')->label('Decisión'),
            TextInput::make('procedure')->label('Procedimiento'),
            DateTimePicker::make('performed_at')->label('Fecha de realización')->seconds(false),
            Textarea::make('barriers')->label('Barreras')->columnSpanFull(),
            Textarea::make('result')->label('Resultado')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('specialty')->label('Especialidad')->placeholder('—'),
                TextColumn::make('decision')->label('Decisión')->placeholder('—'),
                TextColumn::make('procedure')->label('Procedimiento')->placeholder('—'),
                TextColumn::make('performed_at')->label('Realización')->dateTime('d/m/Y H:i')->placeholder('Pendiente'),
            ])
            ->defaultSort('requested_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
