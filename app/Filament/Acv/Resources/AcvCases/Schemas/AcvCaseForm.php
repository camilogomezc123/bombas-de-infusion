<?php

namespace App\Filament\Acv\Resources\AcvCases\Schemas;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AcvCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Control del caso')
                    ->description('Llena la fecha de egreso y el auditor antes de enviar a análisis.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('case_number')
                            ->label('Número de caso')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Se asigna automáticamente'),
                        TextInput::make('admission_number')
                            ->label('Número de ingreso')
                            ->maxLength(80),
                        TextInput::make('resq_code')
                            ->label('Código ResQ')
                            ->uuid(),
                        DateTimePicker::make('discharged_at')
                            ->label('Fecha y hora de egreso')
                            ->seconds(false)
                            ->helperText('Requerida antes de enviar a análisis.'),
                        Select::make('assigned_auditor_id')
                            ->label('Auditor asignado')
                            ->relationship(
                                name: 'assignedAuditor',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query
                                    ->where('role', UserRole::Auditor->value)
                                    ->where('is_active', true)
                            )
                            ->searchable()
                            ->preload()
                            ->disabled(fn (): bool => ! (auth()->user()?->canManageAllCases() ?? false)),
                        Select::make('status')
                            ->label('Estado')
                            ->options(collect(CaseStatus::cases())->mapWithKeys(
                                fn (CaseStatus $status): array => [$status->value => $status->label()]
                            ))
                            ->default(CaseStatus::Hospitalized->value)
                            ->required()
                            ->disabled(fn (): bool => ! (auth()->user()?->canManageAllCases() ?? false))
                            ->helperText('El flujo normal se controla con los botones superiores; aquí solo el líder ajusta el estado por excepción.'),
                        TextInput::make('month')
                            ->label('Mes (auto)')
                            ->placeholder('Se calcula del egreso')
                            ->disabled()
                            ->dehydrated(false)
                            ->maxLength(7),
                    ]),
                Section::make('Paciente')
                    ->columns(3)
                    ->schema([
                        Select::make('patient_id')
                            ->label('Paciente')
                            ->relationship('patient', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->identification} - {$record->full_name}")
                            ->searchable(['identification', 'full_name'])
                            ->preload()
                            ->required()
                            ->columnSpan(2)
                            ->createOptionForm([
                                TextInput::make('identification')->label('Identificación')->required()->maxLength(40),
                                TextInput::make('full_name')->label('Nombre completo')->required()->maxLength(150),
                                TextInput::make('age')->label('Edad')->numeric()->minValue(0)->maxValue(130),
                                Select::make('sex')->label('Sexo')->options([
                                    'Femenino' => 'Femenino',
                                    'Masculino' => 'Masculino',
                                    'Otro' => 'Otro',
                                ]),
                            ]),
                        Toggle::make('is_recurrence')
                            ->label('Recurrencia')
                            ->helperText('Se marca cuando el paciente tiene un ingreso ACV previo.'),
                        TextInput::make('eapb')->label('EAPB')->maxLength(150),
                        Select::make('health_regime')
                            ->label('Régimen de salud')
                            ->options([
                                'Contributivo' => 'Contributivo',
                                'Subsidiado' => 'Subsidiado',
                                'Especial' => 'Especial',
                                'Particular' => 'Particular',
                                'Otro' => 'Otro',
                            ]),
                        Select::make('stroke_type')
                            ->label('Tipo de ACV')
                            ->options([
                                'Isquémico' => 'Isquémico',
                                'TIA' => 'TIA',
                                'Hemorrágico' => 'Hemorrágico',
                                'HSA' => 'HSA',
                                'HIC' => 'HIC',
                                'Trombosis venosa' => 'Trombosis venosa',
                                'Imitador del Ictus' => 'Imitador del Ictus',
                            ]),
                    ]),
                Section::make('Atención inicial')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('arrival_at')->label('Ingreso / hora puerta')->seconds(false),
                        DateTimePicker::make('last_known_well_at')->label('Última vez en buen estado')->seconds(false),
                        DateTimePicker::make('imaging_at')->label('Hora de imagen')->seconds(false),
                        TextInput::make('clinical_data.imaging_type')->label('Imagen realizada'),
                        TextInput::make('clinical_data.nihss_arrival')->label('NIHSS al ingreso')->numeric(),
                        TextInput::make('clinical_data.rankin_arrival')->label('Rankin al ingreso')->numeric(),
                        Toggle::make('clinical_data.code_activated')->label('¿Se activó código?'),
                        Toggle::make('clinical_data.wake_up_stroke')->label('ACV de despertar'),
                        Toggle::make('clinical_data.inpatient_stroke')->label('ACV intrahospitalario'),
                    ]),
                Section::make('Reperfusión')
                    ->columns(3)
                    ->schema([
                        Toggle::make('thrombolysed')->label('Trombolizado'),
                        DateTimePicker::make('thrombolysis_at')->label('Fecha y hora de trombólisis')->seconds(false),
                        TextInput::make('clinical_data.treatment_dose_mg')->label('Dosis en mg')->numeric(),
                        Toggle::make('thrombectomy')->label('Trombectomía'),
                        DateTimePicker::make('groin_puncture_at')->label('Punción inguinal')->seconds(false),
                        DateTimePicker::make('revascularization_at')->label('Revascularización')->seconds(false),
                        TextInput::make('clinical_data.tici')->label('TICI'),
                        Toggle::make('hemorrhagic_transformation')->label('Transformación hemorrágica'),
                        Textarea::make('clinical_data.stroke_cause')->label('Causa del ACV')->rows(2),
                    ]),
                Section::make('Rehabilitación y egreso')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('speech_therapy_at')->label('Valoración por fonoaudiología')->seconds(false),
                        DateTimePicker::make('physiotherapy_at')->label('Valoración por fisioterapia')->seconds(false),
                        Select::make('discharge_destination')
                            ->label('Destino del egreso')
                            ->options([
                                'Alta' => 'Alta hospitalaria',
                                'Remitido' => 'Remitido a otra institución',
                            ])
                            ->placeholder('Sin especificar')
                            ->helperText('Requerido para filtrar la lista de seguimiento.'),
                        Toggle::make('deceased')->label('Fallecido'),
                        Select::make('clinical_data.three_month_contact_type')
                            ->label('Contacto a tres meses')
                            ->options([
                                'Contacto efectivo' => 'Contacto efectivo',
                                'No se contactó' => 'No se contactó',
                                'Paciente fallecido' => 'Paciente fallecido',
                            ]),
                        TextInput::make('clinical_data.rankin_three_months')->label('Rankin a los tres meses')->numeric(),
                        Textarea::make('clinical_data.observations')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Variables originales del registro')
                    ->description('Conserva todas las columnas importadas del archivo histórico.')
                    ->collapsed()
                    ->schema([
                        KeyValue::make('clinical_data.excel')
                            ->label('Variables ACV')
                            ->keyLabel('Variable')
                            ->valueLabel('Valor')
                            ->addable(false)
                            ->deletable(false),
                    ]),
            ]);
    }
}
