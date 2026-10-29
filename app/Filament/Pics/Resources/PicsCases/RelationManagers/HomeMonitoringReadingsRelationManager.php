<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use App\Models\HomeMonitoringReading;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomeMonitoringReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'homeMonitoringReadings';

    protected static ?string $title = 'Monitoreo en casa';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('measured_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('reading_type')->label('Tipo')->badge()
                    ->formatStateUsing(fn (HomeMonitoringReading $record): string => $record->typeLabel()),
                TextColumn::make('value')->label('Valor')
                    ->formatStateUsing(fn (HomeMonitoringReading $record): string => trim("{$record->value} {$record->unit}")),
                TextColumn::make('recordedBy.name')->label('Registrado por')
                    ->getStateUsing(fn (HomeMonitoringReading $record): ?string => $record->recordedBy?->name ?? $record->recordedBy?->full_name)
                    ->placeholder('—'),
                TextColumn::make('notes')->label('Notas')->wrap()->limit(80)->placeholder('—'),
            ])
            ->defaultSort('measured_at', 'desc');
    }

    public function canCreate(): bool
    {
        return false;
    }

    public function canEdit($record): bool
    {
        return false;
    }

    public function canDelete($record): bool
    {
        return false;
    }
}
