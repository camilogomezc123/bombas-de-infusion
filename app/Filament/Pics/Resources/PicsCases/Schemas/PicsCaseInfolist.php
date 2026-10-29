<?php

namespace App\Filament\Pics\Resources\PicsCases\Schemas;

use App\Enums\ClinicalStage;
use App\Models\PicsCase;
use App\Services\PortalEngagementService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class PicsCaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.pics.case-header')->columnSpanFull(),

            Tabs::make('Ruta del caso')
                ->persistTabInQueryString('seccion')
                ->tabs([
                    Tab::make('Resumen')->columns(3)->schema([
                        TextEntry::make('case_number')->label('Caso')->badge(),
                        TextEntry::make('status')->label('Estado')
                            ->formatStateUsing(fn ($state): string => $state?->label() ?? 'Sin estado')->badge(),
                        TextEntry::make('clinical_stage')->label('Etapa clínica')
                            ->formatStateUsing(fn ($state): string => $state?->label() ?? 'Sin etapa')
                            ->badge()
                            ->color(fn ($state): string => match ($state?->value) {
                                'seguimiento' => 'success',
                                'egreso' => 'warning',
                                'hospitalizacion' => 'primary',
                                default => 'gray',
                            }),
                        TextEntry::make('assignedAuditor.name')->label('Responsable de seguimiento')->placeholder('Sin asignar'),
                        TextEntry::make('month')->label('Periodo')->placeholder('En proceso'),
                        TextEntry::make('site.name')->label('Sede')->placeholder('Sin asignar'),
                        TextEntry::make('icuStay.id')->label('Estancia UCI de origen')->placeholder('Sin vincular')
                            ->formatStateUsing(fn (?string $state): string => $state ? "Estancia #{$state}" : 'Sin vincular'),
                        TextEntry::make('enrollment_source')->label('Origen del ingreso')
                            ->formatStateUsing(fn (?string $state): string => PicsCase::ENROLLMENT_SOURCES[$state] ?? 'Sin dato'),
                        TextEntry::make('enrollment_at')->label('Fecha de ingreso al programa')
                            ->dateTime('d/m/Y H:i')->placeholder('Sin dato'),
                        TextEntry::make('discharge_readiness_percentage')
                            ->label('Preparación para el alta')
                            ->badge()
                            ->visible(fn (PicsCase $record): bool => $record->clinical_stage === ClinicalStage::Hospitalizacion)
                            ->state(fn (PicsCase $record): string => $record->dischargeReadinessCheck
                                ? number_format($record->dischargeReadinessCheck->readinessSummary()['percentage'], 1, ',', '.').'%'
                                : 'Sin preparar')
                            ->color(fn (PicsCase $record): string => match (true) {
                                $record->dischargeReadinessCheck === null => 'gray',
                                $record->dischargeReadinessCheck->readinessSummary()['percentage'] >= 90 => 'success',
                                $record->dischargeReadinessCheck->readinessSummary()['percentage'] >= 50 => 'warning',
                                default => 'danger',
                            }),
                    ]),
                    Tab::make('Perfil integral')->schema([
                        Section::make('Paciente y contexto del cuidado')
                            ->description('Resumen compartido para orientar las decisiones del equipo interdisciplinario.')
                            ->icon('heroicon-o-identification')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('patient.full_name')->label('Paciente')->placeholder('Sin paciente'),
                                TextEntry::make('patient.age')->label('Edad')->suffix(' años')->placeholder('Sin dato'),
                                TextEntry::make('patient.sex')->label('Sexo')->placeholder('Sin dato')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'F' => 'Femenino',
                                        'M' => 'Masculino',
                                        default => $state ?: 'Sin dato',
                                    }),
                                TextEntry::make('clinical_data.profile.primary_diagnosis')
                                    ->label('Diagnóstico principal')
                                    ->placeholder('Sin registrar')
                                    ->columnSpan(2),
                                TextEntry::make('clinical_data.profile.service_unit')
                                    ->label('Servicio / unidad')
                                    ->placeholder('Sin registrar'),
                                TextEntry::make('clinical_data.profile.current_condition')
                                    ->label('Situación clínica y evolución relevante')
                                    ->placeholder('Sin registrar')
                                    ->columnSpan(2),
                                TextEntry::make('clinical_data.profile.special_needs')
                                    ->label('Necesidades especiales')
                                    ->placeholder('Sin registrar'),
                            ]),

                        Section::make('Familia y red de cuidado')
                            ->description('Personas autorizadas para acompañar el caso. La autorización de acceso se administra en la sección de relaciones del caso.')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                RepeatableEntry::make('caregiver_network')
                                    ->label('Cuidadores autorizados')
                                    ->state(fn (PicsCase $record): array => $record->caregiverAuthorizations()
                                        ->with('caregiver')
                                        ->orderByDesc('authorized_at')
                                        ->get()
                                        ->map(fn ($authorization): array => [
                                            'name' => $authorization->caregiver?->name ?? 'Cuidador sin nombre',
                                            'relationship' => $authorization->relationship,
                                            'phone' => $authorization->caregiver?->phone,
                                            'email' => $authorization->caregiver?->email,
                                            'access' => $authorization->isActive() ? 'Autorizado' : 'Acceso revocado',
                                        ])
                                        ->all())
                                    ->placeholder('Sin cuidadores registrados')
                                    ->columns(5)
                                    ->schema([
                                        TextEntry::make('name')->label('Nombre'),
                                        TextEntry::make('relationship')->label('Relación')->placeholder('Sin registrar'),
                                        TextEntry::make('phone')->label('Teléfono')->placeholder('Sin registrar'),
                                        TextEntry::make('email')->label('Correo')->placeholder('Sin registrar'),
                                        TextEntry::make('access')->label('Acceso')->badge()
                                            ->color(fn (string $state): string => $state === 'Autorizado' ? 'success' : 'gray'),
                                    ]),
                            ]),

                        Section::make('Comunicación y accesibilidad')
                            ->description('Preferencias para adaptar la conversación y la información entregada.')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('clinical_data.communication.preferred_language')
                                    ->label('Idioma preferido')
                                    ->placeholder('Sin registrar')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'espanol' => 'Español',
                                        'ingles' => 'Inglés',
                                        'lengua_senas' => 'Lengua de señas',
                                        'otro' => 'Otro',
                                        default => $state ?: 'Sin registrar',
                                    }),
                                TextEntry::make('clinical_data.communication.preferred_support')
                                    ->label('Apoyo de comunicación')
                                    ->placeholder('Sin registrar')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'verbal' => 'Explicación verbal',
                                        'visual' => 'Material visual',
                                        'lectura_facil' => 'Lectura fácil',
                                        'verbal_visual' => 'Verbal y visual',
                                        'interprete' => 'Intérprete / mediación',
                                        default => $state ?: 'Sin registrar',
                                    }),
                                TextEntry::make('clinical_data.communication.needs_understanding_support')
                                    ->label('Apoyo para la comprensión')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state === null ? 'Sin registrar' : ($state ? 'Requerido' : 'No requerido'))
                                    ->color(fn ($state): string => $state === null ? 'gray' : ($state ? 'warning' : 'success')),
                                TextEntry::make('clinical_data.communication.barriers')
                                    ->label('Barreras de comunicación')
                                    ->placeholder('Sin registrar'),
                                TextEntry::make('clinical_data.communication.information_contact')
                                    ->label('Persona responsable de recibir información')
                                    ->placeholder('Sin registrar'),
                                TextEntry::make('clinical_data.communication.notes')
                                    ->label('Indicaciones para la comunicación')
                                    ->placeholder('Sin registrar'),
                            ]),

                        Section::make('Educación y comprensión')
                            ->description('Cómo se adapta y registra la educación para el paciente y su cuidador.')
                            ->icon('heroicon-o-academic-cap')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('clinical_data.education.comprehension_status')
                                    ->label('Comprensión observada')
                                    ->placeholder('Sin registrar')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'sin_evaluar' => 'Sin evaluar',
                                        'adecuada' => 'Adecuada',
                                        'requiere_refuerzo' => 'Requiere refuerzo',
                                        'requiere_apoyo_continuo' => 'Requiere apoyo continuo',
                                        default => $state ?: 'Sin registrar',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'adecuada' => 'success',
                                        'requiere_refuerzo', 'requiere_apoyo_continuo' => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('clinical_data.education.recommended_format')
                                    ->label('Formato recomendado')
                                    ->placeholder('Sin registrar')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'visual' => 'Visual',
                                        'verbal' => 'Verbal',
                                        'audio' => 'Audio',
                                        'video' => 'Video',
                                        'visual_verbal' => 'Visual y verbal',
                                        'demostracion' => 'Demostración práctica',
                                        default => $state ?: 'Sin registrar',
                                    }),
                                TextEntry::make('clinical_data.education.caregiver_participation')
                                    ->label('Participación del cuidador')
                                    ->placeholder('Sin registrar')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'activa' => 'Activa',
                                        'parcial' => 'Parcial',
                                        'no_participa' => 'No participa',
                                        'sin_cuidador' => 'Sin cuidador identificado',
                                        default => $state ?: 'Sin registrar',
                                    }),
                                TextEntry::make('clinical_data.education.materials_delivered')
                                    ->label('Educación y materiales entregados')
                                    ->placeholder('Sin registrar')
                                    ->columnSpan(2),
                                TextEntry::make('clinical_data.education.responsible_professional')
                                    ->label('Profesional responsable')
                                    ->placeholder('Sin registrar'),
                                TextEntry::make('clinical_data.education.notes')
                                    ->label('Observaciones')
                                    ->placeholder('Sin registrar')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Consentimientos y autorizaciones')
                            ->description('Estado resumido. Los soportes firmados permanecen en el repositorio institucional correspondiente.')
                            ->icon('heroicon-o-document-check')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('clinical_data.consents.informed_consent_status')
                                    ->label('Consentimiento informado')
                                    ->placeholder('Sin registrar')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'pendiente' => 'Pendiente de verificar',
                                        'registrado' => 'Registrado',
                                        'rechazado' => 'Rechazado',
                                        'revocado' => 'Revocado',
                                        'no_aplica' => 'No aplica',
                                        default => $state ?: 'Sin registrar',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'registrado', 'no_aplica' => 'success',
                                        'pendiente' => 'warning',
                                        'rechazado', 'revocado' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('clinical_data.consents.information_sharing_status')
                                    ->label('Compartir información')
                                    ->placeholder('Sin registrar')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'pendiente' => 'Pendiente de verificar',
                                        'autorizada' => 'Autorizada',
                                        'restringida' => 'Autorizada con restricciones',
                                        'no_autorizada' => 'No autorizada',
                                        'revocada' => 'Revocada',
                                        default => $state ?: 'Sin registrar',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'autorizada' => 'success',
                                        'pendiente', 'restringida' => 'warning',
                                        'no_autorizada', 'revocada' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('clinical_data.consents.recorded_at')
                                    ->label('Fecha de registro')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('Sin registrar'),
                                TextEntry::make('clinical_data.consents.authorized_contact')
                                    ->label('Contacto autorizado')
                                    ->placeholder('Sin registrar'),
                                TextEntry::make('clinical_data.consents.disclosure_restrictions')
                                    ->label('Restricciones de divulgación')
                                    ->placeholder('Sin restricciones registradas'),
                                TextEntry::make('clinical_data.consents.notes')
                                    ->label('Observaciones')
                                    ->placeholder('Sin registrar'),
                            ]),

                        Section::make('Seguimiento y continuidad del cuidado')
                            ->description('Próximos pasos y riesgos operativos que deben acompañar al paciente entre etapas y profesionales.')
                            ->icon('heroicon-o-arrow-path-rounded-square')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('clinical_data.continuity.followup_priority')
                                    ->label('Prioridad de seguimiento')
                                    ->placeholder('Sin registrar')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'baja' => 'Baja',
                                        'media' => 'Media',
                                        'alta' => 'Alta',
                                        default => $state ?: 'Sin registrar',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'alta' => 'danger',
                                        'media' => 'warning',
                                        'baja' => 'success',
                                        default => 'gray',
                                    }),
                                TextEntry::make('clinical_data.continuity.next_control_at')
                                    ->label('Próximo control previsto')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('Sin registrar'),
                                TextEntry::make('clinical_data.continuity.responsible_contact')
                                    ->label('Responsable de continuidad')
                                    ->placeholder('Sin registrar'),
                                TextEntry::make('clinical_data.continuity.support_needs')
                                    ->label('Necesidades de apoyo')
                                    ->placeholder('Sin registrar'),
                                TextEntry::make('clinical_data.continuity.alerts')
                                    ->label('Alertas de continuidad')
                                    ->placeholder('Sin alertas registradas'),
                                TextEntry::make('clinical_data.continuity.next_steps')
                                    ->label('Próximos pasos acordados')
                                    ->placeholder('Sin registrar'),
                            ]),
                    ]),
                    Tab::make('Completitud')->schema([
                        TextEntry::make('completeness_percentage')
                            ->label('Completitud del registro')
                            ->state(fn (PicsCase $record): string => number_format($record->completenessSummary()['percentage'], 1, ',', '.').'%')
                            ->badge()
                            ->color(fn (PicsCase $record): string => match (true) {
                                $record->completenessSummary()['percentage'] >= 90 => 'success',
                                $record->completenessSummary()['percentage'] >= 60 => 'warning',
                                default => 'danger',
                            }),
                        RepeatableEntry::make('completeness_items')
                            ->label('Detalle por campo')
                            ->state(fn (PicsCase $record): array => $record->completenessSummary()['items'])
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
                    Tab::make('Riesgo PICS')->columns(3)->schema([
                        TextEntry::make('risk_level')
                            ->label('Nivel de riesgo')
                            ->badge()
                            ->formatStateUsing(fn (PicsCase $record): string => $record->riskLevelLabel() ?? 'Sin calcular')
                            ->color(fn (PicsCase $record): string => $record->riskLevelColor()),
                        TextEntry::make('risk_score')->label('Puntaje')->placeholder('Sin calcular'),
                        TextEntry::make('mechanical_ventilation_days')->label('Días de ventilación mecánica')->placeholder('Sin dato'),
                        TextEntry::make('delirium_days')->label('Días con delirium')->placeholder('Sin dato'),
                        TextEntry::make('icu_los_days')->label('Estancia en UCI (días)')->placeholder('Sin dato'),
                        TextEntry::make('age_at_admission')->label('Edad al ingreso UCI')->placeholder('Sin dato'),
                        TextEntry::make('barthel_at_discharge')->label('Barthel al egreso')->placeholder('Sin dato'),
                        TextEntry::make('mrc_total')->label('MRC total')->placeholder('Sin dato'),
                        TextEntry::make('handgrip_kg')->label('Handgrip (kg)')->placeholder('Sin dato'),
                        RepeatableEntry::make('risk_factors')
                            ->label('Bitácora del cálculo')
                            ->state(fn (PicsCase $record): array => collect($record->risk_factors ?? [])->map(fn (string $f) => ['factor' => $f])->all())
                            ->columnSpanFull()
                            ->schema([TextEntry::make('factor')->hiddenLabel()])
                            ->visible(fn (PicsCase $record): bool => filled($record->risk_factors)),
                    ]),
                    Tab::make('Trazabilidad del portal')->columns(3)->schema([
                        IconEntry::make('caregiver_authorized')->label('Cuidador autorizado')->boolean()
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['caregiver_authorized']),
                        TextEntry::make('patient_last_login_at')->label('Último ingreso del paciente')
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['patient_last_login_at'])
                            ->dateTime('d/m/Y H:i')->placeholder('Nunca ha ingresado'),
                        TextEntry::make('caregiver_last_login_at')->label('Último ingreso del cuidador')
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['caregiver_last_login_at'])
                            ->dateTime('d/m/Y H:i')->placeholder('Nunca ha ingresado'),
                        TextEntry::make('diary_entries_count')->label('Entradas de diario')
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['diary_entries_count']),
                        TextEntry::make('goal_reports_by_patient')->label('Reportes de metas por el paciente')
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['goal_reports_by_patient']),
                        TextEntry::make('goal_reports_by_caregiver')->label('Reportes de metas por el cuidador')
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['goal_reports_by_caregiver']),
                        TextEntry::make('wellbeing_self_reports_count')->label('Autorreportes de "Cómo me siento"')
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['wellbeing_self_reports_count']),
                        TextEntry::make('support_requests_total')->label('Solicitudes de ayuda (respondidas/total)')
                            ->state(function (PicsCase $record): string {
                                $s = app(PortalEngagementService::class)->caseSnapshot($record);

                                return "{$s['support_requests_answered']} / {$s['support_requests_total']}";
                            }),
                        TextEntry::make('passport_status')->label('Pasaporte de recuperación')->badge()
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['passport_status'])
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'confirmado' => 'Confirmado', 'reportado' => 'Reportado', default => 'Sin diligenciar',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'confirmado' => 'success', 'reportado' => 'warning', default => 'gray',
                            }),
                        TextEntry::make('last_portal_activity_at')->label('Última actividad en el portal')
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['last_portal_activity_at'])
                            ->dateTime('d/m/Y H:i')->placeholder('Sin actividad'),
                        IconEntry::make('care_plan_exists')->label('Plan interdisciplinario')->boolean()
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['care_plan_exists']),
                        TextEntry::make('caregiver_journey')->label('Ruta del cuidador (completados/total)')
                            ->state(function (PicsCase $record): string {
                                $s = app(PortalEngagementService::class)->caseSnapshot($record);

                                return "{$s['caregiver_journey_completed']} / {$s['caregiver_journey_total']}";
                            }),
                        TextEntry::make('discharge_readiness_percentage')->label('Preparación para el alta')
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['discharge_readiness_percentage'])
                            ->formatStateUsing(fn (?float $state): string => $state === null ? 'Sin preparar' : number_format($state, 1, ',', '.').'%'),
                        TextEntry::make('medication_reconciliation_status')->label('Medicamentos')->badge()
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['medication_reconciliation_status'])
                            ->formatStateUsing(fn (string $state): string => $state === 'conciliado' ? 'Conciliados' : 'Sin conciliar')
                            ->color(fn (string $state): string => $state === 'conciliado' ? 'success' : 'gray'),
                        TextEntry::make('home_monitoring_readings_count')->label('Lecturas de monitoreo en casa')
                            ->state(fn (PicsCase $record) => app(PortalEngagementService::class)->caseSnapshot($record)['home_monitoring_readings_count']),
                        TextEntry::make('education')->label('Educación (vistos/asignados)')
                            ->state(function (PicsCase $record): string {
                                $s = app(PortalEngagementService::class)->caseSnapshot($record);

                                return "{$s['education_viewed_count']} / {$s['education_assigned_count']}";
                            }),
                    ]),
                    Tab::make('Paciente')->columns(3)->schema([
                        TextEntry::make('patient.full_name')->label('Paciente'),
                        TextEntry::make('patient.identification')->label('Identificación'),
                    ]),
                    Tab::make('Historial')->schema([
                        TextEntry::make('history_scope')->label('Auditoría del caso')
                            ->state('El historial se consulta en la sección de auditoría del registro.'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
