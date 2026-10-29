<?php

namespace App\Filament\Tep\Resources\TepCases;

use App\Enums\ProgramPermission;
use App\Filament\Tep\Resources\TepCases\Pages\CreateTepCase;
use App\Filament\Tep\Resources\TepCases\Pages\EditTepCase;
use App\Filament\Tep\Resources\TepCases\Pages\ListTepCases;
use App\Filament\Tep\Resources\TepCases\Pages\ViewTepCase;
use App\Filament\Tep\Resources\TepCases\RelationManagers\AdvancedTherapiesRelationManager;
use App\Filament\Tep\Resources\TepCases\RelationManagers\AnticoagulationEpisodesRelationManager;
use App\Filament\Tep\Resources\TepCases\RelationManagers\CtepdEvaluationsRelationManager;
use App\Filament\Tep\Resources\TepCases\RelationManagers\FollowupsRelationManager;
use App\Filament\Tep\Resources\TepCases\RelationManagers\PertActivationsRelationManager;
use App\Models\TepCase;
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
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TepCaseResource extends Resource
{
    protected static ?string $model = TepCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Casos';

    protected static ?string $modelLabel = 'caso TEP';

    protected static ?string $pluralModelLabel = 'Casos TEP';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('clinical_program_id', ProgramAccess::program('TEP')?->id);
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::ViewCases, 'TEP');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::CreateCases, 'TEP');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::EditCases, 'TEP');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Seguridad clínica')->schema([
                Text::make('Este registro apoya la trazabilidad. No diagnostica, prescribe, clasifica automáticamente ni sustituye el juicio clínico o los protocolos aprobados.'),
            ]),
            Tabs::make('Caso TEP')->persistTabInQueryString()->tabs([
                Tab::make('Resumen')->schema([
                    Section::make('Identificación')->columns(3)->schema([
                        Select::make('patient_id')->label('Paciente')->relationship('patient', 'full_name')->searchable()->preload()->required(),
                        TextInput::make('admission_number')->label('Ingreso'),
                        Select::make('site_id')->label('Sede')->relationship('site', 'name')->searchable()->preload(),
                        Select::make('status')->label('Estado')->options(['registered' => 'Registrado', 'in_progress' => 'En proceso', 'pending_audit' => 'Pendiente de auditoría', 'completed' => 'Cerrado'])->default('registered')->required(),
                        Select::make('entry_route')->label('Vía de ingreso')->options(['direct' => 'Directo', 'transfer' => 'Traslado', 'inpatient' => 'Hospitalizado', 'incidental' => 'Hallazgo incidental']),
                        TextInput::make('origin_service')->label('Servicio de origen'),
                        Textarea::make('presentation')->label('Presentación clínica')->columnSpanFull(),
                        DateTimePicker::make('symptom_onset_at')->label('Inicio de síntomas')->seconds(false),
                        DateTimePicker::make('admission_at')->label('Ingreso')->seconds(false),
                        DateTimePicker::make('diagnosis_at')->label('Confirmación diagnóstica')->seconds(false),
                        Toggle::make('tep_suspected')->label('TEP sospechado')->default(true),
                        Toggle::make('tep_confirmed')->label('TEP confirmado'),
                        Toggle::make('incidental_tep')->label('TEP incidental'),
                    ]),
                ]),
                Tab::make('Ruta diagnóstica')->schema([
                    Section::make('Probabilidad clínica pretest')->columns(3)->schema([
                        Select::make('pretest_method')->label('Método documentado')->options(['wells' => 'Wells', 'geneva' => 'Ginebra', 'perc' => 'PERC', 'years' => 'YEARS', 'clinical_judgment' => 'Juicio clínico documentado']),
                        TextInput::make('pretest_score')->label('Puntaje documentado')->numeric(),
                        Select::make('pretest_result')->label('Resultado')->options(['low' => 'Baja', 'intermediate' => 'Intermedia', 'high' => 'Alta', 'not_applicable' => 'No aplica']),
                        Toggle::make('d_dimer_indicated')->label('Dímero D indicado'),
                        TextInput::make('d_dimer_value')->label('Dímero D')->numeric(),
                        TextInput::make('d_dimer_unit')->label('Unidad'),
                        Select::make('diagnostic_imaging')->label('Imagen principal')->options(['ctpa' => 'Angio-TC', 'vq' => 'V/Q', 'echo' => 'Ecocardiograma', 'ultrasound' => 'Ultrasonido venoso', 'other' => 'Otro']),
                        Select::make('imaging_result')->label('Resultado')->options(['positive' => 'Positivo', 'negative' => 'Negativo', 'indeterminate' => 'Indeterminado']),
                        DateTimePicker::make('imaging_ordered_at')->label('Solicitud')->seconds(false),
                        DateTimePicker::make('imaging_completed_at')->label('Realización')->seconds(false),
                    ]),
                ]),
                Tab::make('Clasificación A-E')->schema([
                    Section::make('Clasificación documentada por el equipo clínico')
                        ->description('La plataforma no calcula esta categoría. Las subcategorías permanecerán sin automatización hasta validar y adoptar la guía fuente.')
                        ->columns(3)->schema([
                            Select::make('aha_category')->label('Categoría A-E')->options(TepCase::CATEGORIES),
                            TextInput::make('aha_subcategory')->label('Subcategoría documentada'),
                            DateTimePicker::make('category_documented_at')->label('Fecha de clasificación')->seconds(false),
                            Textarea::make('category_justification')->label('Justificación clínica')->required(fn ($get) => filled($get('aha_category')))->columnSpanFull(),
                            TextInput::make('pesi_score')->label('PESI documentado')->numeric(),
                            Toggle::make('spesi_high_risk')->label('sPESI alto riesgo'),
                            Toggle::make('hestia_positive')->label('Hestia positivo'),
                            Toggle::make('rv_dysfunction')->label('Disfunción VD'),
                            Toggle::make('troponin_positive')->label('Troponina positiva'),
                            Toggle::make('hypotension')->label('Hipotensión'),
                            Toggle::make('shock')->label('Choque'),
                            Toggle::make('cardiac_arrest')->label('Paro cardiaco'),
                        ]),
                ]),
                Tab::make('PERT')->schema([
                    Section::make('Activación y decisión')->columns(2)->schema([
                        Toggle::make('pert_required')->label('PERT requerido'),
                        DateTimePicker::make('pert_activated_at')->label('PERT activado')->seconds(false),
                        Textarea::make('pert_not_activated_reason')->label('Razón documentada de no activación')->columnSpanFull(),
                    ]),
                ]),
                Tab::make('Anticoagulación')->schema([
                    Section::make('Tratamiento documentado')->columns(2)->schema([
                        DateTimePicker::make('anticoagulation_ordered_at')->label('Orden')->seconds(false),
                        DateTimePicker::make('anticoagulation_started_at')->label('Inicio')->seconds(false),
                        Select::make('anticoagulation_type')->label('Tipo')->options(['ufh' => 'Heparina no fraccionada', 'lmwh' => 'HBPM', 'doac' => 'Anticoagulante oral directo', 'vka' => 'Antagonista vitamina K', 'other' => 'Otro']),
                        Textarea::make('anticoagulation_contraindication')->label('Contraindicación o razón de omisión')->columnSpanFull(),
                    ]),
                ]),
                Tab::make('Hospitalización y egreso')->schema([
                    Section::make('Evolución')->columns(3)->schema([
                        Select::make('disposition')->label('Destino')->options(['outpatient' => 'Ambulatorio', 'ward' => 'Hospitalización', 'icu' => 'UCI', 'transfer' => 'Traslado']),
                        Toggle::make('icu_admission')->label('Ingreso UCI'),
                        TextInput::make('icu_stay_days')->label('Estancia UCI (días)')->numeric(),
                        TextInput::make('hospital_stay_days')->label('Estancia hospitalaria (días)')->numeric(),
                        DateTimePicker::make('discharged_at')->label('Egreso')->seconds(false),
                        DateTimePicker::make('death_at')->label('Defunción')->seconds(false),
                        Toggle::make('major_bleeding')->label('Sangrado mayor'),
                        Toggle::make('recurrence_30d')->label('Recurrencia 30 días'),
                        Toggle::make('recurrence_90d')->label('Recurrencia 90 días'),
                        Toggle::make('readmission_30d')->label('Reingreso 30 días'),
                    ]),
                ]),
                Tab::make('Cierre')->schema([
                    Section::make('Validez y cierre')->columns(2)->schema([
                        Toggle::make('is_valid')->label('Caso válido')->default(true),
                        Toggle::make('is_cancelled')->label('Caso cancelado'),
                        Textarea::make('exclusion_reason')->label('Motivo de exclusión o cancelación')->columnSpanFull(),
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
            TextColumn::make('aha_category')->label('A-E')->badge()->placeholder('Sin clasificar'),
            TextColumn::make('admission_at')->label('Ingreso')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('status')->label('Estado')->badge(),
            TextColumn::make('site.name')->label('Sede')->placeholder('—'),
            IconColumn::make('tep_confirmed')->label('Confirmado')->boolean(),
            IconColumn::make('pert_activated_at')->label('PERT')->boolean(fn ($state) => filled($state)),
        ])->filters([
            SelectFilter::make('aha_category')->label('Categoría')->options(TepCase::CATEGORIES),
            SelectFilter::make('status')->options(['registered' => 'Registrado', 'in_progress' => 'En proceso', 'pending_audit' => 'Pendiente de auditoría', 'completed' => 'Cerrado']),
        ])->defaultSort('admission_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListTepCases::route('/'), 'create' => CreateTepCase::route('/create'), 'view' => ViewTepCase::route('/{record}'), 'edit' => EditTepCase::route('/{record}/edit')];
    }

    public static function getRelations(): array
    {
        return [PertActivationsRelationManager::class, AnticoagulationEpisodesRelationManager::class,
            AdvancedTherapiesRelationManager::class, FollowupsRelationManager::class,
            CtepdEvaluationsRelationManager::class];
    }
}
