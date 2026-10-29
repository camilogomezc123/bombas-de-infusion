<?php

namespace App\Filament\Pics\Resources\PicsCases\Schemas;

use App\Enums\CaseStatus;
use App\Enums\ClinicalStage;
use App\Enums\ProgramRole;
use App\Models\PicsCase;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class PicsCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        $managerOnly = fn (): bool => ! (auth()->user()?->canManagePicsCases() ?? false);

        return $schema->components([
            View::make('filament.pics.case-header')->columnSpanFull(),

            Tabs::make('Ruta del caso')
                ->persistTabInQueryString('seccion')
                ->tabs([
                    Tab::make('Resumen')->columns(3)->schema([
                        TextInput::make('case_number')->label('Número de caso')->disabled()->dehydrated(false)
                            ->placeholder('Se asigna automáticamente'),
                        Select::make('status')
                            ->label('Estado')
                            ->options(collect(CaseStatus::cases())->mapWithKeys(fn (CaseStatus $status): array => [$status->value => $status->label()]))
                            ->default(CaseStatus::Assigned->value)
                            ->required()
                            ->disabled($managerOnly)
                            ->helperText('El flujo se controla con las acciones del caso.'),
                        Select::make('clinical_stage')
                            ->label('Etapa clínica')
                            ->options(collect(ClinicalStage::cases())->mapWithKeys(fn (ClinicalStage $stage): array => [$stage->value => $stage->label()]))
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Se controla únicamente con las acciones del caso (Iniciar hospitalización, Confirmar egreso, Iniciar seguimiento).'),
                        Placeholder::make('clinical_stage_dates')
                            ->label('Fechas de la etapa clínica')
                            ->columnSpanFull()
                            ->content(function (?PicsCase $record): string {
                                if (! $record) {
                                    return '—';
                                }

                                return collect([
                                    'UCI' => $record->uci_started_at?->format('d/m/Y H:i'),
                                    'Hospitalización' => $record->hospitalization_started_at?->format('d/m/Y H:i'),
                                    'Egreso' => $record->discharge_confirmed_at
                                        ? $record->discharge_confirmed_at->format('d/m/Y H:i').' · confirmado por '.($record->dischargeConfirmedBy?->name ?? '—')
                                        : null,
                                    'Seguimiento' => $record->followup_started_at?->format('d/m/Y H:i'),
                                ])->filter()->map(fn (string $value, string $label): string => "{$label}: {$value}")->implode(' · ') ?: 'Sin fechas registradas.';
                            }),
                        Select::make('assigned_auditor_id')
                            ->label('Responsable de seguimiento')
                            ->relationship('assignedAuditor', 'name', fn ($query) => $query
                                ->where('is_active', true)
                                ->whereHas('programMemberships', fn ($query) => $query
                                    ->where('role', ProgramRole::Auditor->value)
                                    ->where('is_active', true)
                                    ->whereHas('program', fn ($query) => $query
                                        ->whereRaw('upper(code) = ?', ['PICS']))))
                            ->searchable()
                            ->preload()
                            ->disabled($managerOnly),
                        TextInput::make('month')->label('Periodo')->disabled()->dehydrated(false)
                            ->placeholder('Se calcula automáticamente')
                            ->helperText('Se asigna con la fecha de ingreso al programa.'),
                        Select::make('site_id')->label('Sede')->relationship('site', 'name')->searchable()->preload(),
                        Select::make('icu_stay_id')
                            ->label('Estancia UCI de origen')
                            ->relationship('icuStay', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => 'Estancia #'.$record->id.($record->admission_at ? ' · '.$record->admission_at->format('d/m/Y') : ''))
                            ->searchable()
                            ->preload()
                            ->helperText('Opcional. Vincula este seguimiento con la estancia UCI de ICU Liberation cuando exista.'),
                        Select::make('enrollment_source')
                            ->label('Origen del ingreso al programa')
                            ->options(PicsCase::ENROLLMENT_SOURCES),
                        DateTimePicker::make('enrollment_at')->label('Fecha de ingreso al programa')->seconds(false),
                    ]),

                    Tab::make('Perfil integral')->schema([
                        Section::make('Contexto del paciente y su red de cuidado')
                            ->description('Información clínica y operativa que ayuda al equipo a comprender a la persona y su entorno de cuidado.')
                            ->icon('heroicon-o-identification')
                            ->columns(3)
                            ->schema([
                                TextInput::make('clinical_data.profile.primary_diagnosis')
                                    ->label('Diagnóstico principal')
                                    ->maxLength(180)
                                    ->columnSpan(2),
                                TextInput::make('clinical_data.profile.service_unit')
                                    ->label('Servicio / unidad')
                                    ->maxLength(120),
                                Textarea::make('clinical_data.profile.current_condition')
                                    ->label('Situación clínica y evolución relevante')
                                    ->rows(3)
                                    ->columnSpan(2),
                                Textarea::make('clinical_data.profile.special_needs')
                                    ->label('Necesidades especiales')
                                    ->rows(3),
                                Placeholder::make('caregiver_network_summary')
                                    ->label('Familia y cuidadores autorizados')
                                    ->columnSpanFull()
                                    ->content(function (?PicsCase $record): string {
                                        if (! $record?->exists) {
                                            return 'La red de cuidado se registra después de crear el caso.';
                                        }

                                        $caregivers = $record->caregiverAuthorizations()
                                            ->whereNull('revoked_at')
                                            ->with('caregiver')
                                            ->get()
                                            ->map(function ($authorization): string {
                                                $name = $authorization->caregiver?->name ?? 'Cuidador sin nombre';
                                                $relationship = $authorization->relationship ?: 'Relación sin registrar';

                                                return "{$name} · {$relationship}";
                                            })
                                            ->implode(' | ');

                                        return $caregivers ?: 'Sin cuidadores autorizados. Puede agregarlos en Familia y cuidadores autorizados.';
                                    }),
                            ]),

                        Section::make('Comunicación y accesibilidad')
                            ->description('Preferencias y apoyos para explicar el plan de cuidado con claridad.')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->columns(3)
                            ->schema([
                                Select::make('clinical_data.communication.preferred_language')
                                    ->label('Idioma preferido')
                                    ->options([
                                        'espanol' => 'Español',
                                        'ingles' => 'Inglés',
                                        'lengua_senas' => 'Lengua de señas',
                                        'otro' => 'Otro',
                                    ])
                                    ->placeholder('Sin registrar'),
                                Select::make('clinical_data.communication.preferred_support')
                                    ->label('Apoyo de comunicación')
                                    ->options([
                                        'verbal' => 'Explicación verbal',
                                        'visual' => 'Material visual',
                                        'lectura_facil' => 'Lectura fácil',
                                        'verbal_visual' => 'Verbal y visual',
                                        'interprete' => 'Intérprete / mediación',
                                    ])
                                    ->placeholder('Sin registrar'),
                                Toggle::make('clinical_data.communication.needs_understanding_support')
                                    ->label('Requiere apoyo para la comprensión'),
                                Textarea::make('clinical_data.communication.barriers')
                                    ->label('Barreras de comunicación')
                                    ->rows(3),
                                TextInput::make('clinical_data.communication.information_contact')
                                    ->label('Persona responsable de recibir información')
                                    ->maxLength(150),
                                Textarea::make('clinical_data.communication.notes')
                                    ->label('Indicaciones para la comunicación')
                                    ->rows(3),
                            ]),

                        Section::make('Educación y comprensión')
                            ->description('Registro del formato educativo indicado y de la participación del paciente o cuidador.')
                            ->icon('heroicon-o-academic-cap')
                            ->columns(3)
                            ->schema([
                                Select::make('clinical_data.education.comprehension_status')
                                    ->label('Comprensión observada')
                                    ->options([
                                        'sin_evaluar' => 'Sin evaluar',
                                        'adecuada' => 'Adecuada',
                                        'requiere_refuerzo' => 'Requiere refuerzo',
                                        'requiere_apoyo_continuo' => 'Requiere apoyo continuo',
                                    ])
                                    ->placeholder('Sin registrar'),
                                Select::make('clinical_data.education.recommended_format')
                                    ->label('Formato recomendado')
                                    ->options([
                                        'visual' => 'Visual',
                                        'verbal' => 'Verbal',
                                        'audio' => 'Audio',
                                        'video' => 'Video',
                                        'visual_verbal' => 'Visual y verbal',
                                        'demostracion' => 'Demostración práctica',
                                    ])
                                    ->placeholder('Sin registrar'),
                                Select::make('clinical_data.education.caregiver_participation')
                                    ->label('Participación del cuidador')
                                    ->options([
                                        'activa' => 'Activa',
                                        'parcial' => 'Parcial',
                                        'no_participa' => 'No participa',
                                        'sin_cuidador' => 'Sin cuidador identificado',
                                    ])
                                    ->placeholder('Sin registrar'),
                                Textarea::make('clinical_data.education.materials_delivered')
                                    ->label('Educación y materiales entregados')
                                    ->rows(3)
                                    ->columnSpan(2),
                                TextInput::make('clinical_data.education.responsible_professional')
                                    ->label('Profesional responsable')
                                    ->maxLength(150),
                                Textarea::make('clinical_data.education.notes')
                                    ->label('Observaciones de educación y comprensión')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Consentimientos y autorizaciones')
                            ->description('Estado operativo de los consentimientos. Los documentos firmados deben permanecer en su repositorio institucional.')
                            ->icon('heroicon-o-document-check')
                            ->columns(3)
                            ->schema([
                                Select::make('clinical_data.consents.informed_consent_status')
                                    ->label('Consentimiento informado')
                                    ->options([
                                        'pendiente' => 'Pendiente de verificar',
                                        'registrado' => 'Registrado',
                                        'rechazado' => 'Rechazado',
                                        'revocado' => 'Revocado',
                                        'no_aplica' => 'No aplica',
                                    ])
                                    ->placeholder('Sin registrar'),
                                Select::make('clinical_data.consents.information_sharing_status')
                                    ->label('Autorización para compartir información')
                                    ->options([
                                        'pendiente' => 'Pendiente de verificar',
                                        'autorizada' => 'Autorizada',
                                        'restringida' => 'Autorizada con restricciones',
                                        'no_autorizada' => 'No autorizada',
                                        'revocada' => 'Revocada',
                                    ])
                                    ->placeholder('Sin registrar'),
                                DateTimePicker::make('clinical_data.consents.recorded_at')
                                    ->label('Fecha de registro')
                                    ->seconds(false),
                                TextInput::make('clinical_data.consents.authorized_contact')
                                    ->label('Contacto autorizado')
                                    ->maxLength(150),
                                Textarea::make('clinical_data.consents.disclosure_restrictions')
                                    ->label('Restricciones de divulgación')
                                    ->rows(3),
                                Textarea::make('clinical_data.consents.notes')
                                    ->label('Observaciones')
                                    ->rows(3),
                            ]),

                        Section::make('Seguimiento y continuidad del cuidado')
                            ->description('Necesidades, alertas y próximos pasos para evitar pérdidas de información entre profesionales y etapas de atención.')
                            ->icon('heroicon-o-arrow-path-rounded-square')
                            ->columns(3)
                            ->schema([
                                Select::make('clinical_data.continuity.followup_priority')
                                    ->label('Prioridad de seguimiento')
                                    ->options([
                                        'baja' => 'Baja',
                                        'media' => 'Media',
                                        'alta' => 'Alta',
                                    ])
                                    ->placeholder('Sin registrar'),
                                DateTimePicker::make('clinical_data.continuity.next_control_at')
                                    ->label('Próximo control previsto')
                                    ->seconds(false),
                                TextInput::make('clinical_data.continuity.responsible_contact')
                                    ->label('Responsable de continuidad')
                                    ->maxLength(150),
                                Textarea::make('clinical_data.continuity.support_needs')
                                    ->label('Necesidades de apoyo')
                                    ->rows(3),
                                Textarea::make('clinical_data.continuity.alerts')
                                    ->label('Alertas de continuidad')
                                    ->rows(3),
                                Textarea::make('clinical_data.continuity.next_steps')
                                    ->label('Próximos pasos acordados')
                                    ->rows(3),
                            ]),
                    ]),

                    Tab::make('Completitud')->schema([
                        Placeholder::make('completeness_help')
                            ->label('')
                            ->columnSpanFull()
                            ->content('Un campo vacío se asume "pendiente de diligenciar". Márquelo aquí solo si en realidad no se realizó, no aplica a este caso, o se desconoce.'),
                        Group::make()
                            ->statePath('field_status')
                            ->columns(3)
                            ->schema(collect(PicsCase::KEY_TRACKING_FIELDS)->map(
                                fn (string $label, string $field) => Select::make($field)
                                    ->label($label)
                                    ->options(PicsCase::FIELD_STATUS_OPTIONS)
                                    ->placeholder('Pendiente de diligenciar')
                            )->values()->all()),
                    ]),

                    Tab::make('Riesgo PICS')->columns(3)->schema([
                        TextInput::make('mechanical_ventilation_days')->label('Días de ventilación mecánica')->numeric()->step('0.001')->minValue(0),
                        TextInput::make('delirium_days')->label('Días con delirium (CAM-UCI positivo)')->numeric()->step('0.001')->minValue(0),
                        TextInput::make('icu_los_days')->label('Estancia en UCI (días)')->numeric()->step('0.001')->minValue(0),
                        TextInput::make('sedation_deep_days')->label('Días con sedación profunda')->numeric()->step('0.001')->minValue(0),
                        TextInput::make('age_at_admission')->label('Edad al ingreso UCI (años)')->numeric()->minValue(0)->maxValue(120),
                        TextInput::make('barthel_at_discharge')->label('Índice de Barthel al egreso (0-100)')->numeric()->step('0.1')->minValue(0)->maxValue(100),
                        Toggle::make('shock_or_sepsis')->label('Choque o sepsis en el diagnóstico'),
                        TextInput::make('mrc_total')->label('MRC total (suma 12 grupos musculares, 0-60)')->numeric()->minValue(0)->maxValue(60)
                            ->helperText('< 48 se considera debilidad adquirida en UCI (DAUCI).'),
                        TextInput::make('handgrip_kg')->label('Fuerza de prensión — handgrip (kg, máximo de ambas manos)')->numeric()->step('0.1')->minValue(0)
                            ->helperText('Umbral de alteración: < 16 kg en mujeres, < 27 kg en hombres.'),
                        Placeholder::make('risk_factors_help')
                            ->label('')
                            ->columnSpanFull()
                            ->content('Estos datos alimentan el puntaje de riesgo PICS de 7 factores. Se completan por etapas — usa la acción "Recalcular riesgo" cuando los tengas actualizados.'),
                        Placeholder::make('risk_result')
                            ->label('Resultado del último cálculo')
                            ->columnSpanFull()
                            ->content(fn (?PicsCase $record): string => $record?->risk_level
                                ? $record->riskLevelLabel().' (puntaje '.$record->risk_score.') · '.implode(' · ', $record->risk_factors ?? [])
                                : 'Sin calcular todavía.'),
                    ]),

                    Tab::make('Paciente') ->columns(3)->schema([
                        Select::make('patient_id')
                            ->label('Paciente')
                            ->relationship('patient', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->identification} - {$record->full_name}")
                            ->searchable(['identification', 'full_name'])
                            ->preload()
                            ->required()
                            ->columnSpan(2)
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? 'El paciente no se puede modificar una vez creado el caso.'
                                : null)
                            ->createOptionForm([
                                TextInput::make('identification')->label('Identificación')->required()->maxLength(40),
                                TextInput::make('full_name')->label('Nombre completo')->required()->maxLength(150),
                            ]),
                    ]),

                    Tab::make('Anulación')->columns(3)->schema([
                        Textarea::make('cancellation_reason')->label('Motivo de anulación')->columnSpanFull()
                            ->visible(fn ($get): bool => $get('status') === CaseStatus::Cancelled->value),
                    ]),

                    Tab::make('Historial')->schema([
                        Placeholder::make('history_scope')
                            ->label('Auditoría del caso')
                            ->content('El historial de cambios se consulta en la sección de auditoría del registro.'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
