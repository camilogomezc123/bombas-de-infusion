<?php

namespace App\Filament\Pics\Resources\RecoveryPassports\RelationManagers;

use App\Models\RecoveryPassportItem;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Necesidades y objetivos significativos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')->label('Tipo')->options(RecoveryPassportItem::TYPES)->required()->live(),
            Textarea::make('description')->label('En sus propias palabras')->required()->columnSpanFull(),
            Select::make('status')->label('Estado')->options(RecoveryPassportItem::STATUSES)
                ->visible(fn ($get): bool => $get('type') === RecoveryPassportItem::TYPE_NEED),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('Tipo')->badge()
                    ->formatStateUsing(fn (string $state): string => RecoveryPassportItem::TYPES[$state] ?? $state),
                TextColumn::make('description')->label('Descripción')->wrap(),
                TextColumn::make('status')->label('Estado')->badge()->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => RecoveryPassportItem::STATUSES[$state] ?? '—'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
