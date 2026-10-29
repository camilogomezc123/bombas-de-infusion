<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\Schemas;

use App\Models\SepsisCase;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class SepsisCaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.sepsis.case-header')->columnSpanFull(),

            Tabs::make('Ruta del caso')
                ->persistTabInQueryString('seccion')
                ->tabs([
                    Tab::make('Resumen')->columns(3)->schema([
                        TextEntry::make('case_number')->label('Caso')->badge(),
                        TextEntry::make('status')->label('Estado')
                            ->formatStateUsing(fn ($state): string => $state?->label() ?? 'Sin estado')->badge(),
                        TextEntry::make('assignedAuditor.name')->label('Auditor')->placeholder('Sin asignar'),
                        TextEntry::make('admission_number')->label('N.º ingreso')->placeholder('Sin dato'),
                        TextEntry::make('month')->label('Periodo')->placeholder('En proceso'),
                        TextEntry::make('site.name')->label('Sede')->placeholder('Sin asignar'),
                        TextEntry::make('origin_service')->label('Servicio de origen')->placeholder('Sin dato'),
                        TextEntry::make('acquisition_type')->label('Tipo de adquisición')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'community' => 'Comunitaria', 'hospital' => 'Hospitalaria', default => 'Sin dato',
                            }),
                        TextEntry::make('special_population')->label('Población especial')
                            ->badge()
                            ->placeholder('Ninguna')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'oncologico' => 'Oncológico',
                                'inmunosuprimido' => 'Inmunosuprimido',
                                'gestante' => 'Gestante',
                                'adulto_mayor' => 'Adulto mayor',
                                'ninguna' => 'Ninguna',
                                default => (string) $state,
                            })
                            ->columnSpanFull(),
                    ]),
                    Tab::make('Completitud')->schema([
                        TextEntry::make('completeness_percentage')
                            ->label('Completitud del registro')
                            ->state(fn (SepsisCase $record): string => number_format($record->completenessSummary()['percentage'], 1, ',', '.').'%')
                            ->badge()
                            ->color(fn (SepsisCase $record): string => match (true) {
                                $record->completenessSummary()['percentage'] >= 90 => 'success',
                                $record->completenessSummary()['percentage'] >= 60 => 'warning',
                                default => 'danger',
                            }),
                        RepeatableEntry::make('completeness_items')
                            ->label('Detalle por campo')
                            ->state(fn (SepsisCase $record): array => $record->completenessSummary()['items'])
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('label')->label('Campo')->hiddenLabel(),
                                TextEntry::make('status')->label('Estado')->hiddenLabel()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'filled' => 'Diligenciado',
                                        'not_performed' => 'No realizado',
                                        'not_applicable' => 'No aplica',
                                        'unknown' => 'Desconocido',
                                        default => 'Pendiente de diligenciar',
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'filled', 'not_applicable' => 'success',
                                        'pending' => 'warning',
                                        default => 'danger',
                                    }),
                            ])
                            ->columns(2),
                    ]),
                    Tab::make('Tamizaje y activación')->columns(3)->schema([
                        IconEntry::make('is_valid')->label('Sepsis válida')->boolean(),
                        IconEntry::make('code_activated')->label('Código Sepsis activado')->boolean(),
                        TextEntry::make('activation_at')->label('Fecha y hora de activación')
                            ->dateTime('d/m/Y H:i')->placeholder('Sin dato'),
                        IconEntry::make('goals_of_care_limitation')->label('Limitación de objetivos de atención')->boolean(),
                        TextEntry::make('goals_of_care_notes')->label('Detalle')->placeholder('—')->columnSpanFull()
                            ->visible(fn ($record): bool => (bool) $record->goals_of_care_limitation),
                    ]),
                    Tab::make('Primera hora')->columns(3)->schema([
                        TextEntry::make('lactate_at')->label('Interpretación del lactato')
                            ->dateTime('d/m/Y H:i')->placeholder('Sin dato'),
                        TextEntry::make('antibiotic_at')->label('Administración del antibiótico')
                            ->dateTime('d/m/Y H:i')->placeholder('Sin dato'),
                        TextEntry::make('antibiotics_used')->label('Antibiótico(s) usado(s)')->placeholder('Sin dato'),
                        IconEntry::make('ab_compliance')->label('Cumplimiento AB empírico')->boolean(),
                        IconEntry::make('culture_taken')->label('Se tomaron cultivos')->boolean(),
                        TextEntry::make('culture_at')->label('Toma de cultivos')
                            ->dateTime('d/m/Y H:i')->placeholder('Sin dato'),
                        IconEntry::make('culture_before_ab')->label('Cultivo previo al AB')->boolean(),
                    ]),
                    Tab::make('Tres horas')->columns(3)->schema([
                        TextEntry::make('fluids_at')->label('Administración de vasoactivos')
                            ->dateTime('d/m/Y H:i')->placeholder('Sin dato'),
                        IconEntry::make('map_goal_met')->label('Meta de PAM en 3 h')->boolean()
                            ->visible(fn ($record): bool => (bool) $record->septic_shock),
                    ]),
                    Tab::make('Seis horas')->columns(3)->schema([
                        TextEntry::make('drainage_at')->label('Control de la fuente (drenaje)')
                            ->dateTime('d/m/Y H:i')->placeholder('No aplica'),
                        TextEntry::make('six_hours_scope')->label('Alcance actual')
                            ->state('Solo se presentan los datos disponibles; no se agregaron variables clínicas.'),
                    ]),
                    Tab::make('Hemodinámica')->columns(3)->schema([
                        IconEntry::make('septic_shock')->label('Choque séptico')->boolean(),
                        IconEntry::make('uci')->label('Trasladado a UCI')->boolean(),
                        TextEntry::make('uci_transfer_at')->label('Traslado a UCI')
                            ->dateTime('d/m/Y H:i')->placeholder('No aplica')
                            ->visible(fn ($record): bool => (bool) $record->uci),
                        TextEntry::make('uci_stay_days')->label('Estancia UCI (días)')->placeholder('—')
                            ->visible(fn ($record): bool => (bool) $record->uci),
                    ]),
                    Tab::make('Infección y control del foco')->columns(3)->schema([
                        TextEntry::make('infection_focus')->label('Foco infeccioso')->badge()->placeholder('Sin foco'),
                        IconEntry::make('ab_adjusted')->label('Ajuste de AB según cultivo')->boolean(),
                    ]),
                    Tab::make('Continuidad')->columns(3)->schema([
                        TextEntry::make('admission_at')->label('Ingreso')->dateTime('d/m/Y H:i')->placeholder('Sin dato'),
                        TextEntry::make('discharged_at')->label('Egreso')->dateTime('d/m/Y H:i')->placeholder('Sin egreso'),
                        TextEntry::make('er_stay_days')->label('Estancia urgencias (días)')->placeholder('—'),
                        TextEntry::make('clinic_stay_days')->label('Estancia CDO (días)')->placeholder('—'),
                        IconEntry::make('deceased')->label('Fallecido')->boolean(),
                        TextEntry::make('death_at')->label('Fecha de defunción')
                            ->dateTime('d/m/Y H:i')->placeholder('No aplica')
                            ->visible(fn ($record): bool => (bool) $record->deceased),
                        TextEntry::make('outcome_state')->label('Estado al egreso')->placeholder('—'),
                        TextEntry::make('total_cost')->label('Costo total del caso')
                            ->money('COP')->placeholder('Sin registrar'),
                    ]),
                    Tab::make('Paciente y familia')->columns(3)->schema([
                        TextEntry::make('patient.full_name')->label('Paciente'),
                        TextEntry::make('patient.identification')->label('Identificación'),
                        TextEntry::make('registered_on')->label('Fecha de registro')->date('d/m/Y')->placeholder('Sin dato'),
                        TextEntry::make('caregiver_name')->label('Cuidador / responsable')->placeholder('Sin identificar'),
                        TextEntry::make('caregiver_relationship')->label('Relación')->placeholder('—'),
                        TextEntry::make('caregiver_identified_at')->label('Identificado desde')
                            ->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('family_scope')->label('Educación al paciente y la familia')
                            ->state('Los registros de información, teach-back y preparación para egreso se consultan en la sección "Educación al paciente y la familia".'),
                    ]),
                    Tab::make('Historial')->schema([
                        TextEntry::make('history_scope')->label('Auditoría del caso')
                            ->state('El historial existente se conserva en la sección de auditoría del registro.'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
