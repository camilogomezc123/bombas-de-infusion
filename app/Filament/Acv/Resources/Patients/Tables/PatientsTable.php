<?php

namespace App\Filament\Acv\Resources\Patients\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('identification')
                    ->label('Identificación')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('resq_code')
                    ->label('Código ResQ')
                    ->state(fn ($record): string => $record->cases
                        ->pluck('resq_code')
                        ->filter()
                        ->unique()
                        ->implode(', '))
                    ->placeholder('—')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'cases',
                        fn ($q) => $q->where('resq_code', 'like', "%{$search}%"),
                    )),
                TextColumn::make('age')->label('Edad')->suffix(' años'),
                TextColumn::make('cases_count')
                    ->label('Casos')
                    ->counts('cases')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('full_name');
    }
}
