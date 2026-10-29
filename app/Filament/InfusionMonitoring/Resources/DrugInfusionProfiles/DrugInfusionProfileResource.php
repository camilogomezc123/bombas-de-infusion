<?php

namespace App\Filament\InfusionMonitoring\Resources\DrugInfusionProfiles;

use App\Enums\ProgramPermission;
use App\Filament\InfusionMonitoring\Resources\DrugInfusionProfiles\Pages\CreateDrugInfusionProfile;
use App\Filament\InfusionMonitoring\Resources\DrugInfusionProfiles\Pages\EditDrugInfusionProfile;
use App\Filament\InfusionMonitoring\Resources\DrugInfusionProfiles\Pages\ListDrugInfusionProfiles;
use App\Models\DrugInfusionProfile;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DrugInfusionProfileResource extends Resource
{
    protected static ?string $model = DrugInfusionProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?string $navigationLabel = 'Fármacos y umbrales';

    protected static ?string $modelLabel = 'perfil de fármaco';

    protected static ?string $pluralModelLabel = 'Fármacos y umbrales';

    protected static ?int $navigationSort = 90;

    public static function canViewAny(): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::ViewCases, 'BOMBAS');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::ManageProgram, 'BOMBAS');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::ManageProgram, 'BOMBAS');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación')->columns(3)->schema([
                TextInput::make('name')->label('Nombre')->required()->columnSpan(2),
                Select::make('category')->label('Categoría')->options(DrugInfusionProfile::CATEGORIES)->required(),
                TagsInput::make('aliases')->label('Alias / variantes de nombre reportadas por la bomba')->columnSpanFull(),
            ]),
            Section::make('Score VIS (solo vasoactivos)')->columns(3)->schema([
                Toggle::make('vis_component')->label('Componente del score VIS')->live(),
                TextInput::make('vis_weight')->label('Peso VIS')->numeric()
                    ->visible(fn ($get) => (bool) $get('vis_component')),
                TextInput::make('vis_rate_unit')->label('Unidad de tasa VIS')->placeholder('mcg/kg/min')
                    ->visible(fn ($get) => (bool) $get('vis_component')),
            ]),
            Section::make('Umbrales institucionales de dosis')->columns(3)->schema([
                TextInput::make('max_dose_soft')->label('Límite blando (tasa)')->numeric(),
                TextInput::make('max_dose_hard')->label('Límite duro (tasa)')->numeric(),
                TextInput::make('dose_limit_unit')->label('Unidad')->placeholder('mcg/kg/min'),
                TextInput::make('max_cumulative_dose_per_kg_per_day')->label('Dosis acumulada máxima por día (por kg)')->numeric()
                    ->helperText('Opcional. Se evalúa sobre el total acumulado del día más reciente, en la misma unidad configurada arriba.'),
            ]),
            Section::make('Riesgo de síndrome de infusión (solo propofol)')->columns(3)->schema([
                Toggle::make('pis_risk_applicable')->label('Evaluar riesgo PIS')->live(),
                TextInput::make('pis_dose_threshold')->label('Umbral de dosis (mg/kg/h)')->numeric()
                    ->visible(fn ($get) => (bool) $get('pis_risk_applicable')),
                TextInput::make('pis_duration_threshold_minutes')->label('Duración sostenida mínima (min)')->numeric()
                    ->visible(fn ($get) => (bool) $get('pis_risk_applicable')),
            ]),
            Toggle::make('is_active')->label('Activo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Fármaco')->searchable()->sortable(),
            TextColumn::make('category')->label('Categoría')
                ->formatStateUsing(fn (string $s) => DrugInfusionProfile::CATEGORIES[$s] ?? $s)->badge(),
            IconColumn::make('vis_component')->label('VIS')->boolean(),
            TextColumn::make('max_dose_hard')->label('Límite duro')->placeholder('—'),
            IconColumn::make('pis_risk_applicable')->label('Riesgo PIS')->boolean(),
            IconColumn::make('is_active')->label('Activo')->boolean(),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDrugInfusionProfiles::route('/'),
            'create' => CreateDrugInfusionProfile::route('/create'),
            'edit' => EditDrugInfusionProfile::route('/{record}/edit'),
        ];
    }
}
