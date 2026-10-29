<?php

namespace Tests\Feature\IcuLiberation;

use App\Enums\ProgramRole;
use App\Filament\IcuLiberation\Resources\IcuStays\IcuStayResource;
use App\Models\ClinicalProgram;
use App\Models\IcuStay;
use App\Models\IcuTransferChecklist;
use App\Models\IcuUnit;
use App\Models\Patient;
use App\Models\ProgramMember;
use App\Models\User;
use App\Services\IcuLiberationIndicatorService;
use App\Services\SepsisIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class IcuLiberationFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function program(): ClinicalProgram
    {
        return ClinicalProgram::query()->where('code', 'ICULIB')->firstOrFail();
    }

    private function leaderUser(): User
    {
        $user = User::factory()->create(['email' => 'icul-leader@example.test']);
        ProgramMember::query()->create([
            'clinical_program_id' => $this->program()->id,
            'user_id' => $user->id,
            'role' => ProgramRole::Leader,
            'is_active' => true,
        ]);

        return $user;
    }

    public function test_migration_seeds_program_indicators_and_clinical_rules(): void
    {
        $program = $this->program();

        $this->assertSame('implementation', $program->status);
        $this->assertDatabaseHas('indicator_definitions', ['clinical_program_id' => $program->id, 'code' => 'COMP-01']);
        $this->assertDatabaseHas('indicator_definitions', ['clinical_program_id' => $program->id, 'code' => 'PICS-05']);
        $this->assertDatabaseHas('clinical_rules', ['clinical_program_id' => $program->id, 'code' => 'ICUL-SAT-01', 'status' => 'draft']);
    }

    public function test_foundation_migration_is_reversible(): void
    {
        Artisan::call('migrate:rollback', ['--step' => 100, '--force' => true]);
        Artisan::call('migrate', ['--force' => true]);

        $this->assertTrue(true);
    }

    public function test_icu_stay_has_all_component_relations(): void
    {
        $program = $this->program();
        $unit = IcuUnit::query()->create([
            'clinical_program_id' => $program->id, 'name' => 'UCI de prueba', 'unit_type' => 'adult',
        ]);
        $patient = Patient::query()->create(['identification' => 'ICUL-1', 'full_name' => 'Paciente Prueba']);

        $stay = IcuStay::query()->create([
            'clinical_program_id' => $program->id, 'patient_id' => $patient->id, 'icu_unit_id' => $unit->id,
            'case_number' => 'UCI-TEST-1', 'case_sequence' => 1, 'status' => 'active',
            'admission_at' => now()->subDays(2), 'mechanical_ventilation' => true,
            'ventilation_start_at' => now()->subDays(2),
        ]);

        $stay->painAssessments()->create(['assessed_at' => now(), 'scale' => 'cpot', 'score' => '2']);
        $stay->satTrials()->create(['screened_at' => now(), 'eligible' => true, 'result' => 'success']);
        $stay->sbtTrials()->create(['screened_at' => now(), 'eligible' => true, 'result' => 'success']);
        $stay->sedationAssessments()->create(['assessed_at' => now(), 'goal_value' => '-2', 'actual_value' => '-2']);
        $stay->deliriumAssessments()->create(['assessed_at' => now(), 'tool' => 'cam_icu', 'result' => 'negative']);
        $stay->mobilitySessions()->create(['performed_at' => now(), 'safety_screen_passed' => true, 'achieved_level' => '4']);
        $stay->familyEngagements()->create(['occurred_at' => now(), 'contact_name' => 'Familiar de prueba']);
        $stay->sleepAssessments()->create(['assessed_at' => now()]);
        $stay->physicalRestraints()->create(['restraint_type' => 'Miembros superiores', 'started_at' => now()]);
        $stay->deviceReviews()->create(['device_type' => 'cvc', 'reviewed_at' => now()]);
        $stay->picsFollowups()->create(['checkpoint' => '30d']);
        $stay->audits()->create(['status' => 'pending']);
        $stay->transferChecklist()->create(['cognitive_status_reviewed' => true]);

        $fresh = $stay->fresh([
            'painAssessments', 'satTrials', 'sbtTrials', 'sedationAssessments', 'deliriumAssessments',
            'mobilitySessions', 'familyEngagements', 'sleepAssessments', 'physicalRestraints', 'deviceReviews',
            'picsFollowups', 'audits', 'transferChecklist',
        ]);

        $this->assertCount(1, $fresh->painAssessments);
        $this->assertCount(1, $fresh->satTrials);
        $this->assertCount(1, $fresh->sbtTrials);
        $this->assertCount(1, $fresh->sedationAssessments);
        $this->assertCount(1, $fresh->deliriumAssessments);
        $this->assertCount(1, $fresh->mobilitySessions);
        $this->assertCount(1, $fresh->familyEngagements);
        $this->assertCount(1, $fresh->sleepAssessments);
        $this->assertCount(1, $fresh->physicalRestraints);
        $this->assertCount(1, $fresh->deviceReviews);
        $this->assertCount(1, $fresh->picsFollowups);
        $this->assertCount(1, $fresh->audits);
        $this->assertNotNull($fresh->transferChecklist);
    }

    public function test_transfer_checklist_completion_percentage(): void
    {
        $checklist = new IcuTransferChecklist([
            'cognitive_status_reviewed' => true,
            'functional_status_reviewed' => true,
        ]);

        $total = count(IcuTransferChecklist::CHECKLIST_ITEMS);
        $expected = round((2 / $total) * 100, 1);

        $this->assertSame($expected, $checklist->completionPercentage());
    }

    public function test_indicator_service_computes_bundle_compliance_sat_sbt_and_mortality(): void
    {
        $program = $this->program();
        $patientA = Patient::query()->create(['identification' => 'ICUL-A', 'full_name' => 'Paciente A']);
        $patientB = Patient::query()->create(['identification' => 'ICUL-B', 'full_name' => 'Paciente B']);

        $ventilated = IcuStay::query()->create([
            'clinical_program_id' => $program->id, 'patient_id' => $patientA->id,
            'case_number' => 'UCI-IND-1', 'case_sequence' => 101, 'status' => 'discharged_hospital',
            'admission_at' => now()->subDays(5), 'mechanical_ventilation' => true,
            'ventilation_start_at' => now()->subDays(5), 'extubation_at' => now()->subDays(3),
            'icu_discharge_at' => now()->subDays(2), 'is_valid' => true, 'is_cancelled' => false,
        ]);
        $ventilated->painAssessments()->create(['assessed_at' => now(), 'scale' => 'cpot', 'score' => '1']);
        $ventilated->sedationAssessments()->create(['assessed_at' => now(), 'goal_value' => '-2', 'actual_value' => '-2']);
        $ventilated->deliriumAssessments()->create(['assessed_at' => now(), 'result' => 'positive']);
        $ventilated->mobilitySessions()->create(['performed_at' => now(), 'safety_screen_passed' => true]);
        $ventilated->satTrials()->create(['screened_at' => now(), 'eligible' => true, 'result' => 'success']);
        $ventilated->sbtTrials()->create(['screened_at' => now(), 'eligible' => true, 'result' => 'success']);

        $deceased = IcuStay::query()->create([
            'clinical_program_id' => $program->id, 'patient_id' => $patientB->id,
            'case_number' => 'UCI-IND-2', 'case_sequence' => 102, 'status' => 'deceased',
            'admission_at' => now()->subDays(4), 'death_at' => now()->subDays(1),
            'is_valid' => true, 'is_cancelled' => false,
        ]);

        $dashboard = app(IcuLiberationIndicatorService::class)->dashboard();

        $this->assertSame(2, $dashboard['stays']);
        $this->assertSame(1, $dashboard['ventilated']);
        // La estancia ventilada cumple sus 6 componentes aplicables (4 + SAT + SBT);
        // la fallecida no tiene ningún registro (0/4 aplicables) => 6/10 = 60%.
        $this->assertSame(60.0, $dashboard['bundle_compliance_pct']);
        $this->assertSame(100.0, $dashboard['sat_realized_pct']);
        $this->assertSame(100.0, $dashboard['sbt_realized_pct']);
        $this->assertSame(100.0, $dashboard['delirium_prevalence_pct']);
        $this->assertSame(50.0, $dashboard['hospital_mortality_pct']);
    }

    public function test_indicator_service_does_not_alter_other_programs_indicators(): void
    {
        $before = app(IcuLiberationIndicatorService::class)->dashboard();
        $sepsisSummary = app(SepsisIndicatorService::class)->summary('2026');
        $after = app(IcuLiberationIndicatorService::class)->dashboard();

        $this->assertSame($before, $after);
        $this->assertNull($sepsisSummary);
    }

    public function test_leader_can_access_icu_stays_resource_and_non_member_cannot(): void
    {
        $leader = $this->leaderUser();
        $outsider = User::factory()->create(['email' => 'icul-outsider@example.test']);

        $this->actingAs($leader)
            ->get(IcuStayResource::getUrl('index', panel: 'icu-liberation'))
            ->assertOk();

        $this->actingAs($outsider)
            ->get(IcuStayResource::getUrl('index', panel: 'icu-liberation'))
            ->assertForbidden();
    }

    public function test_icu_stay_create_view_and_edit_forms_render(): void
    {
        $leader = $this->leaderUser();
        $program = $this->program();
        $patient = Patient::query()->create(['identification' => 'ICUL-FORM-1', 'full_name' => 'Paciente Formulario']);
        $stay = IcuStay::query()->create([
            'clinical_program_id' => $program->id, 'patient_id' => $patient->id,
            'case_number' => 'UCI-FORM-1', 'case_sequence' => 301, 'status' => 'active', 'admission_at' => now(),
        ]);

        $this->actingAs($leader);

        $this->get(IcuStayResource::getUrl('create', panel: 'icu-liberation'))->assertOk();
        $this->get(IcuStayResource::getUrl('view', ['record' => $stay], panel: 'icu-liberation'))->assertOk();
        $this->get(IcuStayResource::getUrl('edit', ['record' => $stay], panel: 'icu-liberation'))->assertOk();
    }

    public function test_key_pages_render_for_a_program_member(): void
    {
        $leader = $this->leaderUser();

        $this->actingAs($leader);

        $this->get('/icu-liberation')->assertOk();
        $this->get('/icu-liberation/censo')->assertOk();
        $this->get('/icu-liberation/ronda')->assertOk();
        $this->get('/icu-liberation/indicadores')->assertOk();
        $this->get('/icu-liberation/ruta-clinica')->assertOk();
    }
}
