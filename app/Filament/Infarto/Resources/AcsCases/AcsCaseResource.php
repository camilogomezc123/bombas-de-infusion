<?php

namespace App\Filament\Infarto\Resources\AcsCases;

use App\Enums\ProgramPermission;
use App\Filament\Infarto\Resources\AcsCases\Pages\CreateAcsCase;
use App\Filament\Infarto\Resources\AcsCases\Pages\EditAcsCase;
use App\Filament\Infarto\Resources\AcsCases\Pages\ListAcsCases;
use App\Filament\Infarto\Resources\AcsCases\Pages\ViewAcsCase;
use App\Filament\Infarto\Resources\AcsCases\RelationManagers\AuditsRelationManager;
use App\Filament\Infarto\Resources\AcsCases\RelationManagers\ComplicationsRelationManager;
use App\Filament\Infarto\Resources\AcsCases\RelationManagers\DischargePlanRelationManager;
use App\Filament\Infarto\Resources\AcsCases\RelationManagers\EcgRecordsRelationManager;
use App\Filament\Infarto\Resources\AcsCases\RelationManagers\FollowupsRelationManager;
use App\Filament\Infarto\Resources\AcsCases\RelationManagers\MedicationRecordsRelationManager;
use App\Filament\Infarto\Resources\AcsCases\RelationManagers\PciProceduresRelationManager;
use App\Filament\Infarto\Resources\AcsCases\RelationManagers\RehabilitationReferralsRelationManager;
use App\Filament\Infarto\Resources\AcsCases\RelationManagers\TroponinRecordsRelationManager;
use App\Models\AcsCase;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcsCaseResource extends Resource
{
    protected static ?string $model = AcsCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $navigationLabel = 'Casos';

    protected static ?string $modelLabel = 'caso de SCA';

    protected static ?string $pluralModelLabel = 'Registro de casos';

    protected static ?string $recordTitleAttribute = 'case_number';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user && ProgramAccess::can($user, ProgramPermission::CreateCases, 'INFARTO');
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user && ProgramAccess::can($user, ProgramPermission::EditCases, 'INFARTO');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('program', fn (Builder $q) => $q->where('code', 'INFARTO'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Caso SCA')->columnSpanFull()->tabs([
                Tab::make('Resumen')->schema([
                    Section::make('Identificación')->columns(3)->schema([
                        TextInput::make('case_number')->label('Número de caso')->disabled()->dehydrated(false)->placeholder('Automático'),
                        Select::make('patient_id')->label('Paciente')->relationship('patient', 'full_name')->searchable()->preload()->required(),
                        Select::make('site_id')->label('Sede')->relationship('site', 'name')->searchable()->preload(),
                        TextInput::make('admission_number')->label('Número de ingreso'),
                        Select::make('status')->label('Estado')->options(['registered' => 'Registrado', 'in_progress' => 'En proceso', 'pending_audit' => 'Pendiente de auditoría', 'completed' => 'Cerrado'])->default('registered')->required(),
                        Select::make('assigned_auditor_id')->label('Auditor')->relationship('assignedAuditor', 'name')->searchable()->preload(),
                    ]),
                    Section::make('Clasificación')->columns(3)->schema([
                        Select::make('acs_type')->label('Tipo de SCA')->options(AcsCase::TYPES)->required(),
                        Select::make('infarction_type')->label('Tipo de infarto')->options(['type_1' => 'Tipo 1', 'type_2' => 'Tipo 2', 'minoca' => 'MINOCA', 'scad' => 'Disección coronaria espontánea', 'takotsubo' => 'Takotsubo', 'myocarditis' => 'Miocarditis', 'perioperative' => 'Perioperatorio', 'other' => 'Otro']),
                        TextInput::make('diagnosis')->label('Diagnóstico final'),
                        Select::make('presentation_type')->label('Presentación')->options(['typical' => 'Típica', 'atypical' => 'Atípica']),
                        Select::make('killip_class')->label('Killip')->options(['I' => 'I', 'II' => 'II', 'III' => 'III', 'IV' => 'IV']),
                        TextInput::make('grace_score')->label('Puntaje GRACE documentado')->numeric()->minValue(0)->maxValue(400)
                            ->helperText('Registrar el resultado medido por el equipo clínico; ÁGORA no sustituye la herramienta validada.'),
                        Select::make('grace_risk_category')->label('Categoría GRACE documentada')->options([
                            'low' => 'Riesgo bajo',
                            'intermediate' => 'Riesgo intermedio',
                            'high' => 'Riesgo alto',
                        ]),
                        DateTimePicker::make('grace_assessed_at')->label('Fecha de valoración GRACE')->seconds(false),
                        Select::make('special_populations')->label('Poblaciones especiales')->multiple()->options(['pregnancy' => 'Embarazo', 'oncology' => 'Oncológico', 'frail_older_adult' => 'Adulto mayor frágil', 'high_bleeding_risk' => 'Alto riesgo hemorrágico']),
                    ]),
                ]),
                Tab::make('Ingreso y ECG')->schema([
                    Section::make('Procedencia')->columns(3)->schema([
                        Select::make('entry_route')->label('Ruta de ingreso')->options(['direct' => 'Consulta directa', 'ambulance' => 'Ambulancia', 'transfer' => 'Traslado recibido', 'in_hospital' => 'STEMI intrahospitalario', 'out_of_hospital_arrest' => 'Paro extrahospitalario']),
                        TextInput::make('origin_service')->label('Servicio de origen'),
                        TextInput::make('referral_institution')->label('Institución remitente'),
                        TextInput::make('insurer')->label('Asegurador'),
                        TextInput::make('municipality')->label('Municipio'),
                        Textarea::make('social_barriers')->label('Barreras sociales')->columnSpanFull(),
                    ]),
                    Section::make('Hitos iniciales')->columns(3)->schema([
                        DateTimePicker::make('symptom_onset_at')->label('Inicio de síntomas')->seconds(false),
                        DateTimePicker::make('first_medical_contact_at')->label('Primer contacto médico (FMC)')->seconds(false),
                        DateTimePicker::make('admission_at')->label('Ingreso')->seconds(false)->required(),
                        DateTimePicker::make('ecg_performed_at')->label('ECG realizado')->seconds(false),
                        DateTimePicker::make('ecg_interpreted_at')->label('ECG interpretado')->seconds(false),
                        DateTimePicker::make('diagnosis_at')->label('Diagnóstico')->seconds(false),
                    ]),
                ]),
                Tab::make('STEMI y reperfusión')->schema([
                    Section::make('Código Infarto')->columns(3)->schema([
                        DateTimePicker::make('code_activated_at')->label('Código Infarto activado')->seconds(false),
                        DateTimePicker::make('cath_lab_activated_at')->label('Hemodinamia activada')->seconds(false),
                        DateTimePicker::make('cath_lab_arrival_at')->label('Llegada a sala')->seconds(false),
                        DateTimePicker::make('first_device_at')->label('Primer dispositivo')->seconds(false),
                        DateTimePicker::make('fibrinolysis_at')->label('Fibrinólisis')->seconds(false),
                        DateTimePicker::make('angiography_at')->label('Angiografía')->seconds(false),
                        Toggle::make('eligible_for_reperfusion')->label('Elegible para reperfusión'),
                        Select::make('reperfusion_strategy')->label('Estrategia')->options(['primary_pci' => 'PCI primaria', 'fibrinolysis' => 'Fibrinólisis', 'pharmacoinvasive' => 'Farmacoinvasiva', 'rescue_pci' => 'PCI de rescate', 'none' => 'Sin reperfusión']),
                        Textarea::make('no_reperfusion_reason')->label('Justificación de no reperfusión')->columnSpanFull(),
                    ]),
                ]),
                Tab::make('NSTE-ACS / NSTEMI')->schema([
                    Section::make('Estrategia')->columns(2)->schema([
                        Select::make('nste_strategy')->label('Estrategia seleccionada')->options(['immediate' => 'Invasiva inmediata', 'early' => 'Invasiva temprana ≤24 h', 'in_hospital' => 'Durante hospitalización', 'selective' => 'Rutina o selectiva', 'conservative' => 'Conservadora']),
                        DateTimePicker::make('angiography_at')->label('Hora de angiografía')->seconds(false),
                        Textarea::make('conservative_reason')->label('Contraindicación, decisión compartida o razón de manejo conservador')->columnSpanFull(),
                    ]),
                ]),
                Tab::make('Complicaciones y desenlace')->schema([
                    Section::make('Riesgo y estancia')->columns(3)->schema([
                        Toggle::make('cardiac_arrest')->label('Paro cardiaco'),
                        Toggle::make('cardiogenic_shock')->label('Choque cardiogénico'),
                        Toggle::make('icu_admission')->label('Ingreso a UCI'),
                        TextInput::make('icu_stay_days')->label('Estancia UCI (días)')->numeric(),
                        TextInput::make('hospital_stay_days')->label('Estancia hospitalaria (días)')->numeric(),
                        Select::make('outcome')->label('Desenlace')->options(['alive' => 'Vivo', 'death' => 'Fallecido', 'transfer' => 'Trasladado']),
                        DateTimePicker::make('discharged_at')->label('Egreso')->seconds(false),
                        DateTimePicker::make('death_at')->label('Defunción')->seconds(false),
                        Toggle::make('readmission_30d')->label('Reingreso a 30 días'),
                        Toggle::make('reinfarction_30d')->label('Reinfarto a 30 días'),
                        Toggle::make('mace_30d')->label('MACE a 30 días'),
                    ]),
                ]),
                Tab::make('Cierre')->schema([
                    Section::make('Validez y cierre')->columns(2)->schema([
                        Toggle::make('is_valid')->label('Caso válido')->default(true),
                        Toggle::make('is_cancelled')->label('Caso cancelado'),
                        Textarea::make('exclusion_reason')->label('Exclusión o motivo de cancelación')->columnSpanFull(),
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
            TextColumn::make('acs_type')->label('Tipo')->formatStateUsing(fn (?string $state) => AcsCase::TYPES[$state] ?? $state ?? 'Sin clasificar')->badge(),
            TextColumn::make('admission_at')->label('Ingreso')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('entry_route')->label('Ingreso')->badge(),
            TextColumn::make('site.name')->label('Sede')->placeholder('—'),
            TextColumn::make('status')->label('Estado')->badge(),
            IconColumn::make('cardiogenic_shock')->label('Choque')->boolean(),
        ])->filters([
            SelectFilter::make('acs_type')->label('Tipo de SCA')->options(AcsCase::TYPES),
            SelectFilter::make('status')->options(['registered' => 'Registrado', 'in_progress' => 'En proceso', 'pending_audit' => 'Pendiente de auditoría', 'completed' => 'Cerrado']),
        ])->defaultSort('admission_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [EcgRecordsRelationManager::class, TroponinRecordsRelationManager::class, PciProceduresRelationManager::class,
            MedicationRecordsRelationManager::class, ComplicationsRelationManager::class, DischargePlanRelationManager::class,
            RehabilitationReferralsRelationManager::class, FollowupsRelationManager::class, AuditsRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListAcsCases::route('/'), 'create' => CreateAcsCase::route('/create'), 'view' => ViewAcsCase::route('/{record}'), 'edit' => EditAcsCase::route('/{record}/edit')];
    }
}
