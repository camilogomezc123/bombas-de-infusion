<?php

namespace App\Filament\Pics\Resources\MedicationReconciliations;

use App\Filament\Pics\Resources\MedicationReconciliations\Pages\CreateMedicationReconciliation;
use App\Filament\Pics\Resources\MedicationReconciliations\Pages\EditMedicationReconciliation;
use App\Filament\Pics\Resources\MedicationReconciliations\Pages\ListMedicationReconciliations;
use App\Filament\Pics\Resources\MedicationReconciliations\RelationManagers\ItemsRelationManager;
use App\Models\MedicationReconciliation;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MedicationReconciliationResource extends Resource
{
    protected static ?string $model = MedicationReconciliation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?string $navigationLabel = 'Medicamentos conciliados';

    protected static ?string $modelLabel = 'conciliación de medicamentos';

    protected static ?string $pluralModelLabel = 'Medicamentos conciliados';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('pics_case_id')
                ->label('Caso')
                ->relationship('case', 'case_number')
                ->searchable()->preload()->required()
                ->disabled(fn (string $operation): bool => $operation === 'edit'),
            Select::make('reconciled_by')->label('Conciliado por')
                ->relationship('reconciledBy', 'name')->searchable()->preload(),
            DateTimePicker::make('reconciled_at')->label('Fecha de conciliación')->seconds(false),
            Textarea::make('notes')->label('Notas generales')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case.case_number')->label('Caso')->searchable()->sortable(),
                TextColumn::make('case.patient.full_name')->label('Paciente'),
                TextColumn::make('reconciledBy.name')->label('Conciliado por')->placeholder('Sin asignar'),
                TextColumn::make('reconciled_at')->label('Fecha')->dateTime('d/m/Y H:i')->placeholder('Sin diligenciar'),
                TextColumn::make('items_count')->label('Medicamentos')->counts('items'),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedicationReconciliations::route('/'),
            'create' => CreateMedicationReconciliation::route('/create'),
            'edit' => EditMedicationReconciliation::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
