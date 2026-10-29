<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Models\ClinicalProgram;
use App\Models\PicsCase;
use App\Models\Patient;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PicsDirectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_membership_is_denied_on_direct_panel_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/pics')->assertForbidden();
    }

    public function test_program_viewer_can_open_panel_but_cannot_create_cases(): void
    {
        $user = User::factory()->create();
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $user->id,
            'role' => ProgramRole::Viewer,
        ]);

        $this->actingAs($user)->get('/pics')->assertOk();
        $this->actingAs($user)->get('/pics/pics-cases/create')->assertForbidden();
    }

    public function test_program_leader_can_create_view_and_list_cases(): void
    {
        $user = User::factory()->create();
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $user->id,
            'role' => ProgramRole::Leader,
        ]);

        $patient = Patient::query()->create(['identification' => 'T-1', 'full_name' => 'Paciente de prueba']);
        $case = PicsCase::query()->create(['patient_id' => $patient->id]);

        $this->actingAs($user)->get('/pics/pics-cases')->assertOk();
        $this->actingAs($user)->get('/pics/pics-cases/create')->assertOk();
        $this->actingAs($user)->get("/pics/pics-cases/{$case->id}")->assertOk();
        $this->actingAs($user)->get('/pics/indicadores')->assertOk();

        $case->refresh();
        $this->assertNotNull($case->case_number);
        $this->assertSame('PICS', $case->program->code);
    }
}
