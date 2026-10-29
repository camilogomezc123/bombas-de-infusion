<?php

namespace App\Filament\Sepsis\Resources\ProgramMemberships\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffCompetenciesRelationManager extends RelationManager
{
    protected static string $relationship = 'staffCompetencies';

    protected static ?string $title = 'Competencias evaluadas';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('competency_id')->label('Competencia')->relationship('competency', 'name')->searchable()->preload()->required(),
            Select::make('result')->label('Resultado')->options([
                'approved' => 'Aprobado', 'partial' => 'Aprobado con plan de mejora', 'not_approved' => 'No aprobado',
            ]),
            TextInput::make('score')->label('Puntaje')->numeric(),
            DatePicker::make('approved_on')->label('Fecha de aprobación'),
            DatePicker::make('expires_on')->label('Vigente hasta'),
            TextInput::make('evidence_reference')->label('Evidencia')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('competency.name')->label('Competencia'),
                TextColumn::make('result')
                    ->label('Resultado')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'approved' => 'Aprobado', 'partial' => 'Con plan de mejora', 'not_approved' => 'No aprobado', default => 'Sin evaluar',
                    })
                    ->badge(),
                TextColumn::make('expires_on')->label('Vigente hasta')->date('d/m/Y')->placeholder('Sin fecha'),
            ])
            ->defaultSort('expires_on')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
