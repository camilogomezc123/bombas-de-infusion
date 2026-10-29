<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Models\CarePlan;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarePlanVersioningTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('CP-'), 'full_name' => 'Plan interdisciplinario']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    private function leaderFor(PicsCase $case): User
    {
        $leader = User::factory()->create();
        ProgramMember::query()->create([
            'clinical_program_id' => $case->clinical_program_id, 'user_id' => $leader->id, 'role' => ProgramRole::Leader,
        ]);

        return $leader;
    }

    public function test_creating_a_plan_starts_at_version_one(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $this->actingAs($leader, 'web');

        $plan = CarePlan::query()->create([
            'pics_case_id' => $case->id,
            'general_objective' => 'Recuperar autonomía en actividades básicas',
        ]);

        $this->assertSame(1, $plan->version);
        $this->assertSame($leader->id, $plan->created_by);
        $this->assertSame($leader->id, $plan->updated_by);
        $this->assertNotNull($plan->effective_from);
    }

    public function test_editing_a_versioned_field_archives_the_previous_version(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $this->actingAs($leader, 'web');

        $plan = CarePlan::query()->create([
            'pics_case_id' => $case->id,
            'general_objective' => 'Objetivo original',
            'disciplines' => [['discipline' => 'medicina', 'objective' => 'Control clínico']],
        ]);

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'web');

        $plan->update(['disciplines' => [['discipline' => 'medicina', 'objective' => 'Control clínico ajustado']]]);

        $plan->refresh();
        $this->assertSame(2, $plan->version);
        $this->assertSame($otherUser->id, $plan->updated_by);

        $version = $plan->versions()->first();
        $this->assertSame(1, $version->version);
        $this->assertSame('Objetivo original', $version->general_objective);
        $this->assertSame('Control clínico', $version->disciplines[0]['objective']);
        $this->assertSame($otherUser->id, $version->changed_by);
        $this->assertNotNull($version->effective_until);
    }

    public function test_editing_a_non_versioned_field_does_not_bump_the_version(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $this->actingAs($leader, 'web');

        $plan = CarePlan::query()->create(['pics_case_id' => $case->id, 'general_objective' => 'Objetivo']);

        $plan->update(['effective_from' => today()->addDay()]);

        $this->assertSame(1, $plan->fresh()->version);
        $this->assertSame(0, $plan->versions()->count());
    }

    public function test_resource_pages_render_for_staff(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        CarePlan::query()->create(['pics_case_id' => $case->id, 'general_objective' => 'Objetivo']);

        $this->actingAs($leader)
            ->get('/pics/care-plans')
            ->assertOk();
    }
}
