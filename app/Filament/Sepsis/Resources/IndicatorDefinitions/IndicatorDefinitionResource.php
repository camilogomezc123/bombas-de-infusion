<?php

namespace App\Filament\Sepsis\Resources\IndicatorDefinitions;

use App\Filament\Sepsis\Resources\IndicatorDefinitions\Pages\CreateIndicatorDefinition;
use App\Filament\Sepsis\Resources\IndicatorDefinitions\Pages\EditIndicatorDefinition;
use App\Filament\Sepsis\Resources\IndicatorDefinitions\Pages\ListIndicatorDefinitions;
use App\Filament\Sepsis\Resources\IndicatorDefinitions\Pages\ViewIndicatorDefinition;
use App\Filament\Sepsis\Resources\IndicatorDefinitions\RelationManagers\VersionsRelationManager;
use App\Models\IndicatorDefinition;
use App\Services\AcsIndicatorService;
use App\Services\TepIndicatorService;
use App\Support\CsvExporter;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IndicatorDefinitionResource extends Resource
{
    protected static ?string $model = IndicatorDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Ficha técnica de indicadores';

    protected static ?string $modelLabel = 'indicador';

    protected static ?string $pluralModelLabel = 'Ficha técnica de indicadores';

    protected static ?int $navigationSort = 99;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('clinical_program_id', ProgramAccess::program()?->id)->orderBy('sort_order');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación')
                ->columns(3)
                ->schema([
                    TextInput::make('code')->label('Código')->required(),
                    TextInput::make('name')->label('Nombre')->required()->columnSpan(2),
                    Select::make('indicator_group')->label('Grupo')->options(IndicatorDefinition::GROUPS)->required(),
                    TextInput::make('type')->label('Tipo'),
                    TextInput::make('periodicity')->label('Periodicidad'),
                    Toggle::make('is_core_indicator')->label('Es uno de los 6 indicadores institucionales')
                        ->live()
                        ->helperText('Si se activa, el resultado se lee en vivo del tablero de Indicadores — no se recalcula ni se edita aquí.'),
                    Select::make('core_indicator_key')
                        ->label('Clave del indicador institucional')
                        ->options(fn (): array => ProgramAccess::currentCode() === 'INFARTO'
                            ? collect(AcsIndicatorService::GOALS)->mapWithKeys(
                                fn (array $goal, string $key): array => [$key => $goal['label']],
                            )->all()
                            : (ProgramAccess::currentCode() === 'TEP'
                                ? collect(TepIndicatorService::GOALS)->mapWithKeys(
                                    fn (array $goal, string $key): array => [$key => $goal['label']],
                                )->all()
                            : [
                                'bundle_pct' => 'Adherencia al bundle de primera hora',
                                'map_goal_pct' => 'Meta de PAM en 3 horas',
                                'mort_hosp_sepsis_pct' => 'Mortalidad hospitalaria por sepsis',
                                'mort_hosp_shock_pct' => 'Mortalidad hospitalaria por choque séptico',
                                'mort_30d_sepsis_pct' => 'Mortalidad a 30 días por sepsis',
                                'mort_30d_shock_pct' => 'Mortalidad a 30 días por choque séptico',
                            ]))
                        ->visible(fn ($get): bool => (bool) $get('is_core_indicator'))
                        ->required(fn ($get): bool => (bool) $get('is_core_indicator')),
                ]),
            Section::make('Definición operacional')
                ->columns(2)
                ->schema([
                    Textarea::make('objective')->label('Objetivo')->columnSpanFull(),
                    Textarea::make('operational_definition')->label('Definición operacional')->columnSpanFull(),
                    Textarea::make('numerator')->label('Numerador'),
                    Textarea::make('denominator')->label('Denominador'),
                    Textarea::make('exclusion_criteria')->label('Criterios de exclusión')->columnSpanFull()
                        ->helperText('Casos o situaciones que no deben contarse en el numerador ni el denominador.'),
                    Textarea::make('formula')->label('Fórmula')->columnSpanFull(),
                    TextInput::make('unit')->label('Unidad'),
                    Select::make('expected_direction')->label('Sentido esperado')->options([
                        'higher_better' => 'A mayor valor, mejor', 'lower_better' => 'A menor valor, mejor',
                    ]),
                    TextInput::make('source')->label('Fuente'),
                    Textarea::make('validation_method')->label('Método de validación')->columnSpanFull()
                        ->helperText('Cómo se verifica que el dato fuente sea correcto (doble digitación, auditoría cruzada, etc.).'),
                    Select::make('responsible_user_id')->label('Responsable')->relationship('responsible', 'name')->searchable()->preload(),
                ]),
            Section::make('Versionado')
                ->columns(2)
                ->description('Se actualiza automáticamente al modificar numerador, denominador, exclusiones, fórmula, método de validación o meta. La versión anterior queda archivada en el historial.')
                ->schema([
                    TextInput::make('version')->label('Versión vigente')->disabled()->dehydrated(false),
                    TextInput::make('effective_from')->label('Vigente desde')->disabled()->dehydrated(false),
                ]),
            Section::make('Resultado')
                ->columns(3)
                ->schema([
                    TextInput::make('target_value')->label('Meta'),
                    TextInput::make('baseline_value')->label('Línea base'),
                    TextInput::make('current_result')->label('Resultado actual')
                        ->disabled(fn ($get): bool => (bool) $get('is_core_indicator'))
                        ->dehydrated(fn ($get): bool => ! $get('is_core_indicator'))
                        ->helperText(fn ($get) => $get('is_core_indicator') ? 'Se toma en vivo del tablero de Indicadores.' : null),
                    Select::make('trend')->label('Tendencia')->options([
                        'up' => 'Al alza', 'down' => 'A la baja', 'stable' => 'Estable',
                    ]),
                    Textarea::make('observations')->label('Observaciones')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Código')->searchable(),
                TextColumn::make('name')->label('Indicador')->wrap()->searchable(),
                TextColumn::make('indicator_group')
                    ->label('Grupo')
                    ->formatStateUsing(fn (string $state): string => IndicatorDefinition::GROUPS[$state] ?? $state)
                    ->badge(),
                TextColumn::make('target_value')->label('Meta')->placeholder('—'),
                TextColumn::make('current_result_display')->label('Resultado')
                    ->state(fn (IndicatorDefinition $record): string => $record->is_core_indicator
                        ? (($record->liveResult() !== null) ? number_format((float) $record->liveResult(), 1, ',', '.').'%' : 'Sin dato')
                        : ($record->current_result ?? 'Sin dato'))
                    ->badge(),
                TextColumn::make('is_core_indicator')->label('Fuente')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Tablero de Indicadores' : 'Manual')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('indicator_group')->label('Grupo')->options(IndicatorDefinition::GROUPS),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Exportar CSV')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->action(fn ($livewire) => CsvExporter::stream(
                        'ficha-tecnica-indicadores-'.now()->format('Y-m-d').'.csv',
                        ['Código', 'Indicador', 'Grupo', 'Meta', 'Línea base', 'Resultado', 'Tendencia', 'Fuente', 'Periodicidad'],
                        $livewire->getFilteredTableQuery()->get()->map(fn (IndicatorDefinition $indicator): array => [
                            $indicator->code,
                            $indicator->name,
                            IndicatorDefinition::GROUPS[$indicator->indicator_group] ?? $indicator->indicator_group,
                            $indicator->target_value,
                            $indicator->baseline_value,
                            $indicator->is_core_indicator
                                ? ($indicator->liveResult() !== null ? number_format((float) $indicator->liveResult(), 1, ',', '.').'%' : 'Sin dato')
                                : ($indicator->current_result ?? 'Sin dato'),
                            $indicator->trend,
                            $indicator->is_core_indicator ? 'Tablero de Indicadores' : ($indicator->source ?: 'Manual'),
                            $indicator->periodicity,
                        ]),
                    )),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIndicatorDefinitions::route('/'),
            'create' => CreateIndicatorDefinition::route('/create'),
            'view' => ViewIndicatorDefinition::route('/{record}'),
            'edit' => EditIndicatorDefinition::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [VersionsRelationManager::class];
    }
}
