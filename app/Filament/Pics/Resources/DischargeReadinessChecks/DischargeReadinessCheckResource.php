<?php

namespace App\Filament\Pics\Resources\DischargeReadinessChecks;

use App\Filament\Pics\Resources\DischargeReadinessChecks\Pages\CreateDischargeReadinessCheck;
use App\Filament\Pics\Resources\DischargeReadinessChecks\Pages\EditDischargeReadinessCheck;
use App\Filament\Pics\Resources\DischargeReadinessChecks\Pages\ListDischargeReadinessChecks;
use App\Filament\Pics\Resources\DischargeReadinessChecks\RelationManagers\ItemsRelationManager;
use App\Models\DischargeReadinessCheck;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DischargeReadinessCheckResource extends Resource
{
    protected static ?string $model = DischargeReadinessCheck::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $navigationLabel = 'Preparación para el alta';

    protected static ?string $modelLabel = 'preparación para el alta';

    protected static ?string $pluralModelLabel = 'Preparación para el alta';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('pics_case_id')
                ->label('Caso')
                ->relationship('case', 'case_number')
                ->searchable()->preload()->required()
                ->disabled(fn (string $operation): bool => $operation === 'edit'),
            Select::make('responsible_user_id')->label('Profesional responsable')
                ->relationship('responsible', 'name')->searchable()->preload(),
            Textarea::make('notes')->label('Notas generales')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case.case_number')->label('Caso')->searchable()->sortable(),
                TextColumn::make('case.patient.full_name')->label('Paciente'),
                TextColumn::make('responsible.name')->label('Responsable')->placeholder('Sin asignar'),
                TextColumn::make('readiness_percentage')->label('Comprensión verificada')->badge()
                    ->state(fn (DischargeReadinessCheck $record): string => number_format($record->readinessSummary()['percentage'], 1, ',', '.').'%')
                    ->color(fn (DischargeReadinessCheck $record): string => match (true) {
                        $record->readinessSummary()['percentage'] >= 90 => 'success',
                        $record->readinessSummary()['percentage'] >= 50 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDischargeReadinessChecks::route('/'),
            'create' => CreateDischargeReadinessCheck::route('/create'),
            'edit' => EditDischargeReadinessCheck::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
