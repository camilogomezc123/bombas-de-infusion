<?php

namespace App\Filament\Pics\Pages;

use App\Models\PicsCase;
use App\Services\PortalEngagementService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Computed;

/**
 * Cómo se está comportando el paciente y la familia en el portal — conecta lo que
 * pasa en /portal (logins reales, diario, metas, "Cómo me siento", solicitudes de
 * ayuda) con la vista profesional, complementando los indicadores clínicos de PICS.
 */
class PortalEngagement extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pics.portal-engagement';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $navigationLabel = 'Trazabilidad del portal';

    protected static ?string $title = 'Trazabilidad del portal';

    protected static ?int $navigationSort = 85;

    protected static ?string $slug = 'trazabilidad-portal';

    #[Computed]
    public function summary(): array
    {
        return app(PortalEngagementService::class)->aggregate($this->activeCases());
    }

    #[Computed]
    public function alerts(): array
    {
        return app(PortalEngagementService::class)->inactivityAlerts($this->activeCases());
    }

    #[Computed]
    public function trend(): array
    {
        return app(PortalEngagementService::class)->weeklyActivityTrend($this->activeCases());
    }

    private function activeCases()
    {
        return PicsCase::query()
            ->where('is_valid', true)->where('is_cancelled', false)
            ->with($this->eagerLoad())
            ->get();
    }

    private function eagerLoad(): array
    {
        return [
            'patient', 'caregiverAuthorizations.caregiver', 'diaryEntries', 'recoveryGoals.progressReports',
            'followups', 'supportRequests', 'recoveryPassport', 'carePlan', 'caregiverJourneySteps',
            'dischargeReadinessCheck.items', 'medicationReconciliation.items', 'homeMonitoringReadings',
            'educationAssignments',
        ];
    }

    public function table(Table $table): Table
    {
        $service = app(PortalEngagementService::class);

        return $table
            ->query(PicsCase::query()->where('is_valid', true)->where('is_cancelled', false)->with($this->eagerLoad()))
            ->columns([
                TextColumn::make('case_number')->label('Caso'),
                TextColumn::make('patient.full_name')->label('Paciente'),
                IconColumn::make('caregiver_authorized')->label('Cuidador autorizado')->boolean()
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['caregiver_authorized']),
                TextColumn::make('patient_last_login_at')->label('Último login paciente')
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['patient_last_login_at'])
                    ->dateTime('d/m/Y H:i')->placeholder('Nunca'),
                TextColumn::make('caregiver_last_login_at')->label('Último login cuidador')
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['caregiver_last_login_at'])
                    ->dateTime('d/m/Y H:i')->placeholder('Nunca'),
                TextColumn::make('diary_entries_count')->label('Diario')
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['diary_entries_count']),
                TextColumn::make('goal_reports')->label('Reportes de metas (paciente/cuidador)')
                    ->getStateUsing(function (PicsCase $record) use ($service): string {
                        $s = $service->caseSnapshot($record);

                        return "{$s['goal_reports_by_patient']} / {$s['goal_reports_by_caregiver']}";
                    }),
                TextColumn::make('wellbeing_self_reports_count')->label('Autorreportes de bienestar')
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['wellbeing_self_reports_count']),
                TextColumn::make('support_requests')->label('Solicitudes (resp./total)')
                    ->getStateUsing(function (PicsCase $record) use ($service): string {
                        $s = $service->caseSnapshot($record);

                        return "{$s['support_requests_answered']} / {$s['support_requests_total']}";
                    }),
                TextColumn::make('passport_status')->label('Pasaporte')->badge()
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['passport_status'])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'confirmado' => 'Confirmado', 'reportado' => 'Reportado', default => 'Sin diligenciar',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'confirmado' => 'success', 'reportado' => 'warning', default => 'gray',
                    }),
                TextColumn::make('last_portal_activity_at')->label('Última actividad')
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['last_portal_activity_at'])
                    ->dateTime('d/m/Y H:i')->placeholder('Sin actividad'),
                IconColumn::make('care_plan_exists')->label('Plan interdisciplinario')->boolean()
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['care_plan_exists'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('caregiver_journey')->label('Ruta del cuidador (completados/total)')
                    ->getStateUsing(function (PicsCase $record) use ($service): string {
                        $s = $service->caseSnapshot($record);

                        return "{$s['caregiver_journey_completed']} / {$s['caregiver_journey_total']}";
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discharge_readiness_percentage')->label('Preparación para el alta')
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['discharge_readiness_percentage'])
                    ->formatStateUsing(fn (?float $state): string => $state === null ? 'Sin preparar' : number_format($state, 1, ',', '.').'%')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('medication_reconciliation_status')->label('Medicamentos')->badge()
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['medication_reconciliation_status'])
                    ->formatStateUsing(fn (string $state): string => $state === 'conciliado' ? 'Conciliados' : 'Sin conciliar')
                    ->color(fn (string $state): string => $state === 'conciliado' ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('home_monitoring_readings_count')->label('Lecturas de monitoreo')
                    ->getStateUsing(fn (PicsCase $record) => $service->caseSnapshot($record)['home_monitoring_readings_count'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('education')->label('Educación (vistos/asignados)')
                    ->getStateUsing(function (PicsCase $record) use ($service): string {
                        $s = $service->caseSnapshot($record);

                        return "{$s['education_viewed_count']} / {$s['education_assigned_count']}";
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('case_number')
            ->emptyStateHeading('No hay casos PICS activos todavía');
    }
}
