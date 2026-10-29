<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays;

use App\Enums\ProgramPermission;
use App\Filament\IcuLiberation\Resources\IcuStays\Pages\CreateIcuStay;
use App\Filament\IcuLiberation\Resources\IcuStays\Pages\EditIcuStay;
use App\Filament\IcuLiberation\Resources\IcuStays\Pages\ListIcuStays;
use App\Filament\IcuLiberation\Resources\IcuStays\Pages\ViewIcuStay;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\AuditsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\DeliriumAssessmentsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\DeviceReviewsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\FamilyEngagementsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\MobilitySessionsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\PainAssessmentsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\PhysicalRestraintsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\PicsFollowupsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\RoundsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\SatTrialsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\SbtTrialsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\SedationAssessmentsRelationManager;
use App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers\SleepAssessmentsRelationManager;
use App\Models\IcuStay;
use App\Models\IcuTransferChecklist;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IcuStayResource extends Resource
{
    protected static ?string $model = IcuStay::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Estancias UCI';

    protected static ?string $modelLabel = 'estancia UCI';

    protected static ?string $pluralModelLabel = 'Estancias UCI';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('clinical_program_id', ProgramAccess::program('ICULIB')?->id);
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::ViewCases, 'ICULIB');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::CreateCases, 'ICULIB');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::EditCases, 'ICULIB');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Seguridad clínica')->schema([
                Text::make('Esta plataforma apoya la gestión, trazabilidad y evaluación del Programa ICU Liberation. No sustituye el juicio clínico, la valoración individual, las guías vigentes ni los protocolos institucionales aprobados.'),
            ]),
            Tabs::make('Estancia UCI')->persistTabInQueryString()->tabs([
                Tab::make('Resumen')->schema([
                    Section::make('Identificación')->columns(3)->schema([
                        Select::make('patient_id')->label('Paciente')->relationship('patient', 'full_name')->searchable()->preload()->required(),
                        Select::make('icu_unit_id')->label('UCI')->relationship('icuUnit', 'name')->searchable()->preload(),
                        TextInput::make('bed_label')->label('Cama'),
                        Select::make('site_id')->label('Sede')->relationship('site', 'name')->searchable()->preload(),
                        Select::make('status')->label('Estado')->options(IcuStay::STATUSES)->default('active')->required(),
                        TextInput::make('admission_number')->label('N.º de ingreso'),
                        TextInput::make('admission_diagnosis')->label('Diagnóstico principal')->columnSpan(2),
                        Select::make('admission_source')->label('Servicio de origen')->options([
                            'urgencias' => 'Urgencias', 'hospitalizacion' => 'Hospitalización',
                            'quirofano' => 'Quirófano', 'traslado_externo' => 'Traslado externo', 'otro' => 'Otro',
                        ]),
                        DateTimePicker::make('admission_at')->label('Ingreso a UCI')->seconds(false),
                    ]),
                ]),
                Tab::make('Completitud del bundle')->schema([
                    Placeholder::make('bundle_status_help')
                        ->label('')
                        ->columnSpanFull()
                        ->content('Marque un componente como "No aplica" solo si está correctamente contraindicado o excluido para esta estancia (ej. movilidad con inestabilidad hemodinámica, SAT/SBT sin ventilación). Un componente sin marcar y sin registro se contará como pendiente en el indicador de cumplimiento del bundle.'),
                    Group::make()
                        ->statePath('field_status')
                        ->columns(3)
                        ->schema(collect(IcuStay::BUNDLE_COMPONENTS)->map(
                            fn (string $label, string $key) => Select::make($key)
                                ->label($label)
                                ->options(IcuStay::COMPONENT_STATUS_OPTIONS)
                                ->placeholder('Sin marcar (pendiente si no hay registro)')
                        )->values()->all()),
                ]),
                Tab::make('Ventilación')->schema([
                    Section::make('Ventilación mecánica')->columns(3)->schema([
                        Toggle::make('mechanical_ventilation')->label('Ventilación mecánica')->live(),
                        DateTimePicker::make('ventilation_start_at')->label('Inicio de ventilación')->seconds(false)
                            ->visible(fn ($get): bool => (bool) $get('mechanical_ventilation')),
                        DateTimePicker::make('extubation_at')->label('Extubación')->seconds(false)
                            ->visible(fn ($get): bool => (bool) $get('mechanical_ventilation')),
                        Toggle::make('unplanned_extubation')->label('Autoextubación')
                            ->visible(fn ($get): bool => (bool) $get('mechanical_ventilation')),
                        DateTimePicker::make('reintubation_at')->label('Reintubación')->seconds(false)
                            ->visible(fn ($get): bool => (bool) $get('mechanical_ventilation')),
                        DateTimePicker::make('tracheostomy_at')->label('Traqueostomía')->seconds(false)
                            ->visible(fn ($get): bool => (bool) $get('mechanical_ventilation')),
                    ]),
                ]),
                Tab::make('Traslado y egreso')->schema([
                    Section::make('Egreso de UCI y hospitalario')->columns(3)->schema([
                        DateTimePicker::make('icu_discharge_at')->label('Egreso de UCI')->seconds(false),
                        TextInput::make('icu_discharge_destination')->label('Destino'),
                        DateTimePicker::make('hospital_discharge_at')->label('Egreso hospitalario')->seconds(false),
                        DateTimePicker::make('death_at')->label('Defunción')->seconds(false),
                        TextInput::make('outcome_state')->label('Estado al egreso'),
                        TextInput::make('icu_stay_days')->label('Estancia UCI (días)')->numeric(),
                        TextInput::make('hospital_stay_days')->label('Estancia hospitalaria (días)')->numeric(),
                    ]),
                    Section::make('Checklist de traslado y entrega estructurada')
                        ->relationship('transferChecklist')
                        ->columns(3)
                        ->schema([
                            ...collect(IcuTransferChecklist::CHECKLIST_ITEMS)
                                ->map(fn (string $label, string $field) => Toggle::make($field)->label($label))
                                ->values()->all(),
                            DateTimePicker::make('handoff_at')->label('Fecha y hora de entrega')->seconds(false)->columnSpan(1),
                            TextInput::make('team_delivering')->label('Equipo que entrega')->columnSpan(1),
                            TextInput::make('team_receiving')->label('Equipo que recibe')->columnSpan(1),
                            Toggle::make('understanding_verified')->label('Comprensión verificada')->columnSpan(1),
                            Textarea::make('information_transmitted')->label('Información transmitida')->columnSpanFull(),
                            Textarea::make('pending_items')->label('Pendientes')->columnSpanFull(),
                            Textarea::make('risks')->label('Riesgos')->columnSpanFull(),
                            Textarea::make('rehabilitation_plan')->label('Plan de rehabilitación')->columnSpanFull(),
                        ]),
                ]),
                Tab::make('Cierre')->schema([
                    Section::make('Validez y cierre')->columns(2)->schema([
                        Toggle::make('is_valid')->label('Estancia válida')->default(true),
                        Toggle::make('is_cancelled')->label('Estancia anulada'),
                        Textarea::make('exclusion_reason')->label('Motivo de exclusión o anulación')->columnSpanFull(),
                        DateTimePicker::make('completed_at')->label('Cierre completo')->seconds(false),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('case_number')->label('Caso')->searchable()->sortable(),
            TextColumn::make('patient.full_name')->label('Paciente')->searchable(),
            TextColumn::make('icuUnit.name')->label('UCI')->placeholder('—'),
            TextColumn::make('bed_label')->label('Cama')->placeholder('—'),
            TextColumn::make('admission_at')->label('Ingreso')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('status')->label('Estado')
                ->formatStateUsing(fn (string $state): string => IcuStay::STATUSES[$state] ?? $state)->badge(),
            IconColumn::make('mechanical_ventilation')->label('Ventilado')->boolean(),
            TextColumn::make('site.name')->label('Sede')->placeholder('—'),
        ])->filters([
            SelectFilter::make('status')->label('Estado')->options(IcuStay::STATUSES),
        ])->defaultSort('admission_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIcuStays::route('/'),
            'create' => CreateIcuStay::route('/create'),
            'view' => ViewIcuStay::route('/{record}'),
            'edit' => EditIcuStay::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RoundsRelationManager::class, PainAssessmentsRelationManager::class, SatTrialsRelationManager::class,
            SbtTrialsRelationManager::class, SedationAssessmentsRelationManager::class,
            DeliriumAssessmentsRelationManager::class, MobilitySessionsRelationManager::class,
            FamilyEngagementsRelationManager::class, SleepAssessmentsRelationManager::class,
            PhysicalRestraintsRelationManager::class, DeviceReviewsRelationManager::class,
            PicsFollowupsRelationManager::class, AuditsRelationManager::class,
        ];
    }
}
