<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PicsPanelUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private function leaderUser(PicsCase $case): User
    {
        $leader = User::factory()->create();
        ProgramMember::query()->create([
            'clinical_program_id' => $case->clinical_program_id,
            'user_id' => $leader->id,
            'role' => ProgramRole::Leader,
        ]);

        return $leader;
    }

    public function test_case_view_page_renders_with_the_new_screening_instruments_and_risk_tab(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'PANEL-1', 'full_name' => 'Panel Test', 'sex' => 'F']);
        $case = PicsCase::query()->create([
            'clinical_program_id' => $program->id,
            'patient_id' => $patient->id,
            'age_at_admission' => 70,
            'icu_los_days' => 9,
        ]);
        $case->followups()->create([
            'checkpoint' => '7d',
            'respondent_type' => 'paciente',
            'amt_score' => 4,
            'hads_ansiedad' => 12,
            'phq9_score' => 15,
        ]);

        $leader = $this->leaderUser($case);

        $response = $this->actingAs($leader)->get("/pics/pics-cases/{$case->id}");

        $response->assertOk();
        $response->assertSee('Riesgo PICS');
        $response->assertSee('Sin calcular'); // aún no se ha corrido "Recalcular riesgo"
    }

    public function test_recalculate_risk_action_updates_the_case(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'PANEL-2', 'full_name' => 'Panel Test 2']);
        $case = PicsCase::query()->create([
            'clinical_program_id' => $program->id,
            'patient_id' => $patient->id,
            'age_at_admission' => 70,
            'shock_or_sepsis' => true,
        ]);

        $case->recalculateRisk()->save();
        $case->refresh();

        $this->assertSame('alto', $case->risk_level);
        $this->assertSame(4, $case->risk_score);
    }

    public function test_recovery_goal_resource_still_works_after_the_upgrade(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'PANEL-3', 'full_name' => 'Panel Test 3']);
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
        $leader = $this->leaderUser($case);

        $this->actingAs($leader)->get('/pics/recovery-goals')->assertOk();
        $this->actingAs($leader)->get('/pics/recovery-goals/create')->assertOk();
    }
}
