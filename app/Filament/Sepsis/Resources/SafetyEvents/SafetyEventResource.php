<?php

namespace App\Filament\Sepsis\Resources\SafetyEvents;

use App\Filament\Sepsis\Resources\SafetyEvents\Pages\CreateSafetyEvent;
use App\Filament\Sepsis\Resources\SafetyEvents\Pages\EditSafetyEvent;
use App\Filament\Sepsis\Resources\SafetyEvents\Pages\ListSafetyEvents;
use App\Filament\Sepsis\Resources\SafetyEvents\Pages\ViewSafetyEvent;
use App\Models\SepsisSafetyEvent;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SafetyEventResource extends Resource
{
    protected static ?string $model = SepsisSafetyEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|\UnitEnum|null $navigationGroup = 'Calidad y mejora';

    protected static ?string $navigationLabel = 'Seguridad del programa';

    protected static ?string $modelLabel = 'evento de seguridad';

    protected static ?string $pluralModelLabel = 'Seguridad del programa';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('program', fn (Builder $query) => $query->where('code', 'SEPSIS'))
            ->with(['case', 'responsible']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Evento')
                ->columns(3)
                ->schema([
                    Select::make('event_type')->label('Tipo de evento')->options(SepsisSafetyEvent::EVENT_TYPES)->required(),
                    Select::make('event_category')->label('Categoría')->options(SepsisSafetyEvent::EVENT_CATEGORIES)->default('incident')->required()->live(),
                    DatePicker::make('occurred_on')->label('Fecha')->required(),
                    DateTimePicker::make('event_at')->label('Hora exacta del evento')->seconds(false)
                        ->helperText('Si el evento corresponde a una tarea puntual del bundle, registra su hora exacta.'),
                    TextInput::make('service')->label('Servicio'),
                    Select::make('sepsis_case_id')
                        ->label('Caso relacionado')
                        ->relationship('case', 'case_number')
                        ->searchable()
                        ->preload()
                        ->live(),
                    Select::make('sepsis_bundle_task_id')
                        ->label('Tarea del bundle relacionada')
                        ->relationship(
                            'bundleTask',
                            'label',
                            fn (Builder $query, $get) => $query->when(
                                $get('sepsis_case_id'),
                                fn (Builder $query, $caseId) => $query->where('sepsis_case_id', $caseId),
                            ),
                        )
                        ->searchable()
                        ->preload()
                        ->helperText('Opcional — enlaza el evento con el registro exacto que lo originó, para agilizar el análisis de 5 Porqués.'),
                    Select::make('severity')->label('Severidad')->options([
                        'low' => 'Leve', 'moderate' => 'Moderada', 'high' => 'Alta', 'sentinel' => 'Centinela',
                    ])->required()->live(),
                    Select::make('harm_level')->label('Nivel de daño')->options([
                        'none' => 'Sin daño', 'mild' => 'Leve', 'moderate' => 'Moderado', 'severe' => 'Severo', 'death' => 'Muerte',
                    ]),
                    Textarea::make('description')->label('Descripción')->required()->columnSpanFull(),
                ]),
            Section::make('Análisis y mejora')
                ->columns(2)
                ->schema([
                    Select::make('status')->label('Estado')->options(SepsisSafetyEvent::STATUSES)->default('open')->required(),
                    Select::make('responsible_user_id')->label('Responsable')->relationship('responsible', 'name')->searchable()->preload(),
                    DatePicker::make('due_on')->label('Fecha comprometida'),
                    TextInput::make('evidence_reference')->label('Evidencia')->maxLength(1000),
                    Textarea::make('immediate_action')->label('Acción inmediata')->columnSpanFull(),
                    Textarea::make('analysis')->label('Análisis causal (registro rápido)')->columnSpanFull(),
                    Select::make('assessment_finding_id')
                        ->label('Análisis formal de causa raíz (5 porqués)')
                        ->relationship('finding', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => "Hallazgo #{$record->id}".($record->root_cause ? " — {$record->root_cause}" : ''))
                        ->searchable()
                        ->preload()
                        ->columnSpanFull()
                        ->helperText('Para eventos de alto daño o centinela: enlaza un hallazgo formal con 5 porqués en vez de duplicar el análisis aquí.')
                        ->visible(fn ($get): bool => in_array($get('event_category'), ['adverse_event', 'sentinel_event'], true) || $get('severity') === 'sentinel'),
                    Textarea::make('improvement_action')->label('Acción de mejora')->columnSpanFull(),
                    Textarea::make('effectiveness_verification')->label('Verificación de efectividad')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_on')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('event_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => SepsisSafetyEvent::EVENT_TYPES[$state] ?? $state)
                    ->badge()
                    ->wrap(),
                TextColumn::make('event_category')
                    ->label('Categoría')
                    ->formatStateUsing(fn (string $state): string => SepsisSafetyEvent::EVENT_CATEGORIES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sentinel_event' => 'danger',
                        'adverse_event' => 'warning',
                        'near_miss' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('severity')
                    ->label('Severidad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sentinel' => 'danger',
                        'high' => 'warning',
                        'moderate' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => SepsisSafetyEvent::STATUSES[$state] ?? $state)
                    ->badge(),
                TextColumn::make('case.case_number')->label('Caso')->placeholder('—'),
                TextColumn::make('responsible.name')->label('Responsable')->placeholder('Sin asignar'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(SepsisSafetyEvent::STATUSES),
                SelectFilter::make('event_category')->label('Categoría')->options(SepsisSafetyEvent::EVENT_CATEGORIES),
                SelectFilter::make('severity')->label('Severidad')->options([
                    'low' => 'Leve', 'moderate' => 'Moderada', 'high' => 'Alta', 'sentinel' => 'Centinela',
                ]),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->defaultSort('occurred_on', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSafetyEvents::route('/'),
            'create' => CreateSafetyEvent::route('/create'),
            'view' => ViewSafetyEvent::route('/{record}'),
            'edit' => EditSafetyEvent::route('/{record}/edit'),
        ];
    }
}
