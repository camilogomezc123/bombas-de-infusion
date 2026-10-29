<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\ClinicalProgram;
use App\Models\EducationResource;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use App\Services\PortalEngagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalEngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_real_login_records_last_login_at_for_patient_and_caregiver(): void
    {
        $patient = Patient::query()->create(['identification' => 'L-1', 'full_name' => 'L', 'email' => 'l@test.com', 'password' => Hash::make('secret')]);
        $caregiver = Caregiver::query()->create(['name' => 'C', 'email' => 'c-login@test.com', 'password' => Hash::make('secret')]);

        $this->assertNull($patient->last_login_at);
        $this->assertNull($caregiver->last_login_at);

        Auth::guard('patient')->login($patient);
        Auth::guard('caregiver')->login($caregiver);

        $this->assertNotNull($patient->fresh()->last_login_at);
        $this->assertNotNull($caregiver->fresh()->last_login_at);
    }

    public function test_case_snapshot_reports_accurate_portal_engagement_counts(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'E-1', 'full_name' => 'Engagement', 'email' => 'e@test.com']);
        $patient->forceFill(['last_login_at' => now()->subDay()])->save();
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
        $staff = User::factory()->create();
        $caregiver = Caregiver::query()->create(['name' => 'Cg', 'email' => 'cg@test.com', 'password' => Hash::make('secret')]);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id, 'can_write_diary' => true,
            'authorized_by' => $staff->id, 'authorized_at' => now(),
        ]);

        $case->diaryEntries()->create(['authorable_type' => Caregiver::class, 'authorable_id' => $caregiver->id, 'entry_date' => now(), 'content' => 'x']);

        $goal = $case->recoveryGoals()->create(['domain' => 'movilidad', 'description' => 'x', 'status' => 'active']);
        $goal->progressReports()->create(['reporter_type' => Patient::class, 'reporter_id' => $patient->id, 'reported_at' => now()]);
        $goal->progressReports()->create(['reporter_type' => Caregiver::class, 'reporter_id' => $caregiver->id, 'reported_at' => now()]);
        $goal->progressReports()->create(['reporter_type' => Caregiver::class, 'reporter_id' => $caregiver->id, 'reported_at' => now()]);

        $case->followups()->create([
            'checkpoint' => '7d', 'respondent_type' => 'paciente',
            'submitted_by_type' => Patient::class, 'submitted_by_id' => $patient->id,
            'confirmed_by' => $staff->id, 'confirmed_at' => now(),
        ]);

        $request = $case->supportRequests()->create([
            'type' => 'dificultad', 'description' => 'x', 'created_by_type' => Patient::class, 'created_by_id' => $patient->id,
            'created_at' => now()->subHours(4),
        ]);
        $request->update(['responded_by' => $staff->id, 'responded_at' => now()]);

        $case->recoveryPassport()->create(['mobility_before' => 'x', 'reported_at' => now(), 'is_confirmed' => true]);

        $snapshot = app(PortalEngagementService::class)->caseSnapshot($case->fresh([
            'patient', 'caregiverAuthorizations.caregiver', 'diaryEntries', 'recoveryGoals.progressReports', 'followups', 'supportRequests', 'recoveryPassport',
        ]));

        $this->assertTrue($snapshot['caregiver_authorized']);
        $this->assertNotNull($snapshot['patient_last_login_at']);
        $this->assertSame(1, $snapshot['diary_entries_count']);
        $this->assertSame(3, $snapshot['goal_reports_total']);
        $this->assertSame(1, $snapshot['goal_reports_by_patient']);
        $this->assertSame(2, $snapshot['goal_reports_by_caregiver']);
        $this->assertSame(1, $snapshot['wellbeing_self_reports_count']);
        $this->assertSame(1, $snapshot['wellbeing_confirmed_count']);
        $this->assertSame(1, $snapshot['support_requests_total']);
        $this->assertSame(1, $snapshot['support_requests_answered']);
        $this->assertNotNull($snapshot['support_requests_avg_response_hours']);
        $this->assertSame('confirmado', $snapshot['passport_status']);
        $this->assertNotNull($snapshot['last_portal_activity_at']);
    }

    public function test_portal_engagement_page_and_case_tab_render_for_staff(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'E-2', 'full_name' => 'Otro caso']);
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);

        $leader = User::factory()->create();
        ProgramMember::query()->create([
            'clinical_program_id' => $case->clinical_program_id, 'user_id' => $leader->id, 'role' => ProgramRole::Leader,
        ]);

        $this->actingAs($leader)
            ->get('/pics/trazabilidad-portal')
            ->assertOk()
            ->assertSee('Trazabilidad del portal');

        $this->actingAs($leader)
            ->get("/pics/pics-cases/{$case->id}")
            ->assertOk()
            ->assertSee('Trazabilidad del portal');

        $this->actingAs($leader)
            ->get('/pics/indicadores')
            ->assertOk()
            ->assertSee('Trazabilidad del portal');
    }

    public function test_alert_fires_when_caregiver_authorized_days_ago_never_logged_in(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'AL-1', 'full_name' => 'Alerta']);
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
        $staff = User::factory()->create();
        $caregiver = Caregiver::query()->create(['name' => 'C', 'email' => 'al@test.com', 'password' => Hash::make('x')]);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id,
            'authorized_by' => $staff->id, 'authorized_at' => now()->subDays(10),
        ]);

        $case->load(['patient', 'caregiverAuthorizations.caregiver', 'diaryEntries', 'recoveryGoals.progressReports', 'followups', 'supportRequests', 'recoveryPassport']);

        $alerts = app(PortalEngagementService::class)->inactivityAlerts(collect([$case]));

        $reasons = collect($alerts)->pluck('reason');
        $this->assertContains('cuidador_sin_ingresar', $reasons);
        $this->assertContains('sin_actividad', $reasons);
    }

    public function test_no_alert_when_caregiver_logged_in_recently_and_case_has_activity(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'AL-2', 'full_name' => 'Sin alerta']);
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
        $staff = User::factory()->create();
        $caregiver = Caregiver::query()->create(['name' => 'C2', 'email' => 'al2@test.com', 'password' => Hash::make('x')]);
        $caregiver->forceFill(['last_login_at' => now()->subHour()])->save();
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id,
            'authorized_by' => $staff->id, 'authorized_at' => now()->subDays(10),
        ]);
        $case->diaryEntries()->create(['authorable_type' => Caregiver::class, 'authorable_id' => $caregiver->id, 'entry_date' => now(), 'content' => 'x']);

        $case->load(['patient', 'caregiverAuthorizations.caregiver', 'diaryEntries', 'recoveryGoals.progressReports', 'followups', 'supportRequests', 'recoveryPassport']);

        $alerts = app(PortalEngagementService::class)->inactivityAlerts(collect([$case]));

        $this->assertEmpty($alerts);
    }

    public function test_weekly_trend_counts_portal_originated_activity_in_the_current_week(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'TR-1', 'full_name' => 'Tendencia']);
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
        $case->diaryEntries()->create(['authorable_type' => Patient::class, 'authorable_id' => $patient->id, 'entry_date' => now(), 'content' => 'x']);

        $case->load(['patient', 'caregiverAuthorizations.caregiver', 'diaryEntries', 'recoveryGoals.progressReports', 'followups', 'supportRequests', 'recoveryPassport']);

        $trend = app(PortalEngagementService::class)->weeklyActivityTrend(collect([$case]), 4);

        $this->assertCount(4, $trend);
        $this->assertSame(1, $trend[3]['value']); // última semana = la actual
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

    public function test_case_snapshot_includes_etapa_2_and_3_modules(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'EG-1', 'full_name' => 'Egreso']);
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);

        $case->carePlan()->create(['general_objective' => 'x']);

        $case->caregiverJourneySteps()->create(['title' => 'Paso 1', 'reported_at' => now()]);
        $case->caregiverJourneySteps()->create(['title' => 'Paso 2']);

        $check = $case->dischargeReadinessCheck()->create([]);
        $check->items()->create(['topic' => 'medicamentos', 'understood' => true]);
        $check->items()->create(['topic' => 'signos_alarma', 'understood' => false]);

        $reconciliation = $case->medicationReconciliation()->create([]);
        $reconciliation->items()->create(['medication_name' => 'Enalapril']);

        $case->homeMonitoringReadings()->create(['reading_type' => 'spo2', 'value' => '97', 'measured_at' => now()]);
        $case->homeMonitoringReadings()->create(['reading_type' => 'heart_rate', 'value' => '80', 'measured_at' => now()]);

        $resource = EducationResource::query()->create(['title' => 'Contenido', 'category' => 'general']);
        $case->educationAssignments()->create(['education_resource_id' => $resource->id, 'viewed_at' => now()]);

        $case->load($this->eagerLoad());
        $snapshot = app(PortalEngagementService::class)->caseSnapshot($case);

        $this->assertTrue($snapshot['care_plan_exists']);
        $this->assertSame(2, $snapshot['caregiver_journey_total']);
        $this->assertSame(1, $snapshot['caregiver_journey_completed']);
        $this->assertSame(50.0, $snapshot['discharge_readiness_percentage']);
        $this->assertSame('conciliado', $snapshot['medication_reconciliation_status']);
        $this->assertSame(2, $snapshot['home_monitoring_readings_count']);
        $this->assertSame(1, $snapshot['education_assigned_count']);
        $this->assertSame(1, $snapshot['education_viewed_count']);
    }

    public function test_aggregate_computes_discharge_preparation_percentages(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'EG-2', 'full_name' => 'Egreso 2']);
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
        $case->carePlan()->create(['general_objective' => 'x']);
        $reconciliation = $case->medicationReconciliation()->create([]);
        $reconciliation->items()->create(['medication_name' => 'Enalapril']);

        $program2 = $program;
        $patient2 = Patient::query()->create(['identification' => 'EG-3', 'full_name' => 'Egreso 3']);
        $caseWithoutPrep = PicsCase::query()->create(['clinical_program_id' => $program2->id, 'patient_id' => $patient2->id]);

        $cases = PicsCase::query()->whereIn('id', [$case->id, $caseWithoutPrep->id])->with($this->eagerLoad())->get();

        $summary = app(PortalEngagementService::class)->aggregate($cases);

        $this->assertSame(50.0, $summary['care_plan_pct']);
        $this->assertSame(50.0, $summary['medication_reconciliation_pct']);
    }
}
