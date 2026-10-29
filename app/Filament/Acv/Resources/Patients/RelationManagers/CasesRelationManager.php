<?php

namespace App\Filament\Acv\Resources\Patients\RelationManagers;

use App\Filament\Acv\Resources\AcvCases\AcvCaseResource;
use App\Filament\Acv\Resources\AcvCases\Tables\AcvCasesTable;
use App\Models\AcvCase;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class CasesRelationManager extends RelationManager
{
    protected static string $relationship = 'cases';

    protected static ?string $title = 'Casos del paciente';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('case_number')
            ->columns(AcvCasesTable::columns())
            ->recordActions([
                Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->url(fn (AcvCase $record): string => AcvCaseResource::getUrl('view', ['record' => $record])),
                Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-m-pencil-square')
                    ->visible(fn (AcvCase $record): bool => AcvCaseResource::canEdit($record))
                    ->url(fn (AcvCase $record): string => AcvCaseResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('case_sequence', 'desc');
    }
}
