<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\Schemas;

use App\Enums\CaseStatus;
use App\Enums\ProgramRole;
use App\Models\SepsisCase;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class SepsisCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        $managerOnly = fn (): bool => ! (auth()->user()?->canManageSepsisCases() ?? false);

        return $schema->components([
            View::make('filament.sepsis.case-header')->columnSpanFull(),

            Tabs::make('Ruta del caso')
                ->persistTabInQueryString('seccion')
                ->tabs([
                    Tab::make('Resumen')->columns(3)->schema([
                        TextInput::make('case_number')->label('Número de caso')->disabled()->dehydrated(false)
                            ->placeholder('Se asigna automáticamente'),
                        TextInput::make('admission_number')->label('Número de ingreso')->maxLength(80),
                        Select::make('status')
                            ->label('Estado')
                            ->options(collect(CaseStatus::cases())->mapWithKeys(fn (CaseStatus $status): array => [$status->value => $status->label()]))
                            ->default(CaseStatus::Hospitalized->value)
                            ->required()
                            ->disabled($managerOnly)
                            ->helperText('El flujo se controla con las acciones del caso.'),
                        Select::make('assigned_auditor_id')
                            ->label('Auditor asignado')
                            ->relationship('assignedAuditor', 'name', fn ($query) => $query
                                ->where('is_active', true)
                                ->whereHas('programMemberships', fn ($query) => $query
                                    ->where('role', ProgramRole::Auditor->value)
                                    ->where('is_active', true)
                                    ->whereHas('program', fn ($query) => $query
                                        ->whereRaw('upper(code) = ?', ['SEPSIS']))))
                            ->searchable()
                            ->preload()
                            ->disabled($managerOnly),
                        TextInput::make('month')->label('Periodo')->disabled()->dehydrated(false)
                            ->placeholder('Se calcula automáticamente')
                            ->helperText('Se asigna con la fecha de egreso.'),
                        Select::make('site_id')->label('Sede')->relationship('site', 'name')->searchable()->preload(),
                        Select::make('origin_service')
                            ->label('Servicio de origen')
                            ->options([
                                'Urgencias' => 'Urgencias',
                                'Hospitalización' => 'Hospitalización',
                                'UCI' => 'UCI',
                                'UTMO' => 'Unidad de Trasplante de Médula Ósea',
                                'Remisión externa' => 'Remisión externa',
                            ])
                            ->searchable(),
                        Select::make('acquisition_type')->label('Tipo de adquisición')->options([
                            'community' => 'Comunitaria', 'hospital' => 'Hospitalaria',
                        ]),
                        CheckboxList::make('special_population')
                            ->label('Población especial')
                            ->options([
                                'oncologico' => 'Oncológico',
                                'inmunosuprimido' => 'Inmunosuprimido',
                                'gestante' => 'Gestante',
                                'adulto_mayor' => 'Adulto mayor',
                                'ninguna' => 'Ninguna',
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),

                    Tab::make('Completitud')->schema([
                        Placeholder::make('completeness_help')
                            ->label('')
                            ->columnSpanFull()
                            ->content('Un campo vacío se asume "pendiente de diligenciar". Márquelo aquí solo si en realidad no se realizó, no aplica a este caso, o se desconoce — así no se interpreta como un dato faltante por error.'),
                        Group::make()
                            ->statePath('field_status')
                            ->columns(3)
                            ->schema(collect(SepsisCase::KEY_TRACKING_FIELDS)->map(
                                fn (string $label, string $field) => Select::make($field)
                                    ->label($label)
                                    ->options(SepsisCase::FIELD_STATUS_OPTIONS)
                                    ->placeholder('Pendiente de diligenciar')
                            )->values()->all()),
                    ]),

                    Tab::make('Tamizaje y activación')->columns(3)->schema([
                        Toggle::make('is_valid')->label('Sepsis válida')->default(true)->disabled($managerOnly),
                        Toggle::make('code_activated')->label('Se activó Código Sepsis')->inline(false),
                        DateTimePicker::make('activation_at')->label('Fecha y hora de activación')->seconds(false),
                        Toggle::make('goals_of_care_limitation')->label('Tiene limitación de objetivos de atención')->live()->inline(false)
                            ->helperText('Ej: orden de no reanimar previa, cuidado paliativo. Se usa para el análisis de mortalidad ajustado por riesgo.'),
                        Textarea::make('goals_of_care_notes')->label('Detalle de la limitación')->columnSpan(2)
                            ->visible(fn ($get): bool => (bool) $get('goals_of_care_limitation')),
                    ]),

                    Tab::make('Primera hora')->columns(3)->schema([
                        DateTimePicker::make('lactate_at')->label('Interpretación del lactato')->seconds(false),
                        DateTimePicker::make('antibiotic_at')->label('Administración del antibiótico')->seconds(false),
                        TextInput::make('antibiotics_used')->label('Antibiótico(s) usado(s)')->maxLength(255),
                        Toggle::make('ab_compliance')->label('Cumplimiento AB empírico')->inline(false),
                        Toggle::make('culture_taken')->label('Se tomaron cultivos')->inline(false),
                        DateTimePicker::make('culture_at')->label('Fecha y hora de cultivos')->seconds(false),
                        Toggle::make('culture_before_ab')->label('Cultivo previo al antibiótico')->inline(false),
                    ]),

                    Tab::make('Tres horas')->columns(3)->schema([
                        DateTimePicker::make('fluids_at')->label('Administración de vasoactivos')->seconds(false),
                        Toggle::make('map_goal_met')->label('Meta de PAM alcanzada en las primeras 3 h')->inline(false)
                            ->helperText('PAM ≥ 65 mmHg dentro de las 3 horas posteriores a la activación.')
                            ->visible(fn ($get): bool => (bool) $get('septic_shock')),
                    ]),

                    Tab::make('Seis horas')->columns(3)->schema([
                        DateTimePicker::make('drainage_at')->label('Control de la fuente (drenaje)')->seconds(false),
                        Placeholder::make('six_hours_scope')
                            ->label('Alcance actual')
                            ->content('Esta sección presenta únicamente los datos disponibles. No agrega variables clínicas nuevas.'),
                    ]),

                    Tab::make('Hemodinámica')->columns(3)->schema([
                        Toggle::make('septic_shock')->label('Choque séptico')->live()->inline(false),
                        Toggle::make('uci')->label('Trasladado a UCI')->live()->inline(false),
                        DateTimePicker::make('uci_transfer_at')->label('Fecha y hora de traslado a UCI')->seconds(false)
                            ->visible(fn ($get): bool => (bool) $get('uci'))
                            ->afterOrEqual('admission_at'),
                        TextInput::make('uci_stay_days')->label('Estancia en UCI (días)')->numeric()->step('0.001')->minValue(0)
                            ->visible(fn ($get): bool => (bool) $get('uci')),
                    ]),

                    Tab::make('Infección y control del foco')->columns(3)->schema([
                        Select::make('infection_focus')
                            ->label('Foco infeccioso')
                            ->options([
                                'Abdominal' => 'Abdominal',
                                'Respiratorio' => 'Respiratorio',
                                'Urinario' => 'Urinario',
                                'Piel y tejidos blandos' => 'Piel y tejidos blandos',
                                'Sistema nervioso central' => 'Sistema nervioso central',
                                'Catéter / dispositivo intravascular' => 'Catéter / dispositivo intravascular',
                                'Multifocal' => 'Multifocal',
                                'Sin foco identificado' => 'Sin foco identificado',
                                'Otro' => 'Otro',
                            ])
                            ->searchable(),
                        Toggle::make('ab_adjusted')->label('Ajuste de AB según cultivo')->inline(false),
                    ]),

                    Tab::make('Continuidad')->columns(3)->schema([
                        DateTimePicker::make('admission_at')->label('Fecha y hora de ingreso')->seconds(false),
                        DateTimePicker::make('discharged_at')->label('Fecha y hora de egreso')->seconds(false)
                            ->afterOrEqual('admission_at')
                            ->validationMessages(['afterOrEqual' => 'El egreso no puede ser anterior al ingreso.']),
                        TextInput::make('er_stay_days')->label('Estancia en urgencias (días)')->numeric()->step('0.001')->minValue(0),
                        TextInput::make('clinic_stay_days')->label('Estancia en clínica / CDO (días)')->numeric()->step('0.001')->minValue(0),
                        Toggle::make('deceased')->label('Fallecido')->live()->inline(false),
                        DateTimePicker::make('death_at')->label('Fecha y hora de defunción')->seconds(false)
                            ->visible(fn ($get): bool => (bool) $get('deceased'))
                            ->afterOrEqual('admission_at')
                            ->validationMessages(['afterOrEqual' => 'La defunción no puede ser anterior al ingreso.']),
                        TextInput::make('outcome_state')->label('Estado al egreso')->maxLength(150),
                        TextInput::make('total_cost')->label('Costo total del caso')->numeric()->step('0.01')->minValue(0)->prefix('$'),
                    ]),

                    Tab::make('Paciente y familia')->columns(3)->schema([
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
                        DateTimePicker::make('registered_on')->label('Fecha de registro')->seconds(false),
                        TextInput::make('caregiver_name')->label('Cuidador / responsable de decisiones')
                            ->helperText('Identificarlo desde el ingreso, no solo al egreso.'),
                        TextInput::make('caregiver_relationship')->label('Relación con el paciente'),
                        DateTimePicker::make('caregiver_identified_at')->label('Fecha de identificación del cuidador')->seconds(false),
                        Placeholder::make('family_scope')
                            ->label('Educación al paciente y la familia')
                            ->columnSpanFull()
                            ->content('Los registros de información, teach-back y preparación para egreso se administran en la sección "Educación al paciente y la familia" al guardar el caso.'),
                    ]),

                    Tab::make('Historial')->schema([
                        Placeholder::make('history_scope')
                            ->label('Auditoría del caso')
                            ->content('El historial de cambios existente se conserva y se consulta en la sección de auditoría del registro.'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
