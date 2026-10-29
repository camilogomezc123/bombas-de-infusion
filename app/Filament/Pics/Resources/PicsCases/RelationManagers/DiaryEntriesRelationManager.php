<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use App\Models\DiaryEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiaryEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'diaryEntries';

    protected static ?string $title = 'Diario de recuperación';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entry_date')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('author')->label('Autor')
                    ->getStateUsing(fn (DiaryEntry $record): string => $record->authorLabel()),
                TextColumn::make('content')->label('Relato del día')->wrap()->limit(150),
                IconColumn::make('visible_to_patient')->label('Visible al paciente')->boolean(),
            ])
            ->defaultSort('entry_date', 'desc');
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
