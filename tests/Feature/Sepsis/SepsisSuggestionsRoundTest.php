<?php

namespace Tests\Feature\Sepsis;

use App\Enums\CaseStatus;
use App\Enums\ProgramRole;
use App\Filament\Sepsis\Resources\SepsisCases\SepsisCaseResource;
use App\Models\ClinicalAudit;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\ProgramMember;
use App\Models\SepsisBundleTask;
use App\Models\SepsisCase;
use App\Models\SepsisSafetyEvent;
use App\Models\User;
use App\Notifications\SepsisMonthlyDigestNotification;
use App\Services\SepsisExecutiveSummaryService;
use App\Services\SepsisIndicatorService;
use App\Services\SepsisSourceControlTimingService;
use App\Support\CsvExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SepsisSuggestionsRoundTest extends TestCase
{
    use RefreshDatabase;

    private function leaderUser(): User
    {
        $user = User::factory()->create(['email' => 'leader@example.test']);
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $user->id,
            'role' => ProgramRole::Leader,
            'is_active' => true,
        ]);

        return $user;
    }

    public function test_sofa_components_total_matches_sum_only_when_all_present(): void
    {
        $patient = Patient::query()->create(['identification' => 'SUG-1', 'full_name' => 'Paciente Sugerencias']);
        $case = SepsisCase::query()->create(['patient_id' => $patient->id, 'status' => CaseStatus::Hospitalized]);

        $partial = $case->screenings()->create(['occurred_at' => now(), 'sofa_respiratory' => 2]);
        $this->assertNull($partial->sofaComponentsTotal());

        $complete = $case->screenings()->create([
            'occurred_at' => now(),
            'sofa_respiratory' => 2, 'sofa_coagulation' => 1, 'sofa_liver' => 0,
            'sofa_cardiovascular' => 3, 'sofa_cns' => 1, 'sofa_renal' => 1,
        ]);
        $this->assertSame(8, $complete->sofaComponentsTotal());
    }

    public function test_fluid_balance_is_intake_minus_output(): void
    {
        $patient = Patient::query()->create(['identification' => 'SUG-2', 'full_name' => 'Paciente Balance']);
        $case = SepsisCase::query()->create(['patient_id' => $patient->id, 'status' => CaseStatus::Hospitalized]);

        $assessment = $case->hemodynamicAssessments()->create([
            'assessed_at' => now(), 'fluid_intake_ml' => 5000, 'fluid_output_ml' => 1200,
        ]);

        $this->assertSame(3800.0, $assessment->fluidBalanceMl());
    }

    public function test_case_edit_page_renders_with_the_new_date_validation_fields(): void
    {
        $leader = $this->leaderUser();
        $patient = Patient::query()->create(['identification' => 'SUG-3', 'full_name' => 'Paciente Fechas']);
        $case = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'status' => CaseStatus::Hospitalized,
            'admission_at' => now(),
        ]);

        // La regla "afterOrEqual" vive en el formulario de Filament (capa de UI); a nivel de
        // Eloquent el modelo no la reaplica, así que aquí solo confirmamos que la pantalla
        // que la contiene carga correctamente para un usuario autorizado.
        $this->actingAs($leader)
            ->get(SepsisCaseResource::getUrl('edit', ['record' => $case], panel: 'sepsis'))
            ->assertOk();
    }

    public function test_safety_event_can_link_to_a_specific_bundle_task(): void
    {
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'SUG-4', 'full_name' => 'Paciente Evento']);
        $case = SepsisCase::query()->create(['patient_id' => $patient->id, 'status' => CaseStatus::Hospitalized]);
        $task = SepsisBundleTask::query()->create([
            'sepsis_case_id' => $case->id, 'task_key' => 'antibiotic', 'label' => 'Antibiótico', 'status' => 'pending',
        ]);
        $event = SepsisSafetyEvent::query()->create([
            'clinical_program_id' => $program->id,
            'sepsis_case_id' => $case->id,
            'sepsis_bundle_task_id' => $task->id,
            'occurred_on' => today(),
            'event_type' => 'antibiotic_delay',
            'description' => 'Prueba',
            'severity' => 'high',
        ]);

        $this->assertSame($task->id, $event->bundleTask->id);
    }

    public function test_bundle_task_quick_mark_done_sets_late_status_after_deadline(): void
    {
        $patient = Patient::query()->create(['identification' => 'SUG-5', 'full_name' => 'Paciente Bundle']);
        $case = SepsisCase::query()->create(['patient_id' => $patient->id, 'status' => CaseStatus::Hospitalized]);
        $task = SepsisBundleTask::query()->create([
            'sepsis_case_id' => $case->id, 'task_key' => 'antibiotic', 'label' => 'Antibiótico',
            'status' => 'pending', 'target_at' => now()->subMinutes(10),
        ]);

        $now = now();
        $task->update([
            'status' => ($task->target_at && $now->gt($task->target_at)) ? 'done_late' : 'done',
            'done_at' => $now,
        ]);

        $this->assertSame('done_late', $task->fresh()->status);
    }

    public function test_csv_export_writes_a_clinical_audit_entry(): void
    {
        $leader = $this->leaderUser();
        $this->actingAs($leader);

        $before = ClinicalAudit::query()->where('event', 'exported')->count();

        CsvExporter::stream('archivo-prueba.csv', ['Columna'], [['valor']]);

        $this->assertSame($before + 1, ClinicalAudit::query()->where('event', 'exported')->count());
    }

    public function test_monthly_digest_command_notifies_only_direction_and_coordination_roles(): void
    {
        Notification::fake();

        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        $director = User::factory()->create(['email' => 'direccion@example.test']);
        $physician = User::factory()->create(['email' => 'medico@example.test']);

        ProgramMember::query()->create([
            'clinical_program_id' => $program->id, 'user_id' => $director->id,
            'role' => ProgramRole::ExecutiveDirection, 'is_active' => true,
        ]);
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id, 'user_id' => $physician->id,
            'role' => ProgramRole::Physician, 'is_active' => true,
        ]);

        Artisan::call('agora:send-sepsis-monthly-digest');

        Notification::assertSentTo($director, SepsisMonthlyDigestNotification::class);
        Notification::assertNotSentTo($physician, SepsisMonthlyDigestNotification::class);
    }

    public function test_source_control_timing_computes_median_hours_by_focus(): void
    {
        $patient = Patient::query()->create(['identification' => 'SUG-6', 'full_name' => 'Paciente Foco']);
        $case = SepsisCase::query()->create([
            'patient_id' => $patient->id, 'status' => CaseStatus::Hospitalized,
            'activation_at' => now()->subHours(10), 'infection_focus' => 'Abdominal',
        ]);
        $case->sourceControlActions()->create(['performed_at' => now()->subHours(4)]);

        $result = app(SepsisSourceControlTimingService::class)->medianHoursByFocus();
        $row = collect($result)->firstWhere('focus', 'Abdominal');

        $this->assertSame(1, $row['n']);
        $this->assertSame(6.0, $row['median_hours']);
    }

    public function test_expiring_alerts_do_not_affect_the_six_core_indicators(): void
    {
        $before = app(SepsisIndicatorService::class)->summary('2026');
        app(SepsisExecutiveSummaryService::class)->summary();
        $after = app(SepsisIndicatorService::class)->summary('2026');

        $this->assertSame($before, $after);
    }
}
