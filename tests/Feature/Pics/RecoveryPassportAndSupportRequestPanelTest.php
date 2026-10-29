<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Models\Caregiver;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\RecoveryPassport;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecoveryPassportAndSupportRequestPanelTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('PP-'), 'full_name' => 'Pasaporte Test']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    private function leaderFor(PicsCase $case): User
    {
        $leader = User::factory()->create();
        ProgramMember::query()->create([
            'clinical_program_id' => $case->clinical_program_id,
            'user_id' => $leader->id,
            'role' => ProgramRole::Leader,
        ]);

        return $leader;
    }

    public function test_recovery_passport_resource_pages_render(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);

        $this->actingAs($leader)->get('/pics/recovery-passports')->assertOk();
        $this->actingAs($leader)->get('/pics/recovery-passports/create')->assertOk();
    }

    public function test_professional_can_confirm_a_passport_reported_by_the_family(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $passport = RecoveryPassport::query()->create([
            'pics_case_id' => $case->id,
            'mobility_before' => 'Caminaba sin ayuda',
            'reported_by_type' => Caregiver::class,
            'reported_by_id' => 1,
            'reported_at' => now(),
        ]);

        $this->actingAs($leader)
            ->get("/pics/recovery-passports/{$passport->id}/edit")
            ->assertOk()
            ->assertSee('Confirmar pasaporte');

        $passport->update(['is_confirmed' => true, 'confirmed_by' => $leader->id, 'confirmed_at' => now()]);
        $this->assertTrue($passport->fresh()->is_confirmed);
    }

    public function test_support_request_resource_lists_requests_and_workflow_actions_transition_status(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $request = SupportRequest::query()->create([
            'pics_case_id' => $case->id,
            'type' => 'dificultad',
            'description' => 'Dolor al caminar',
            'priority' => 'media',
            'status' => 'nueva',
            'created_by_type' => Patient::class,
            'created_by_id' => $case->patient_id,
        ]);

        $this->actingAs($leader)->get('/pics/support-requests')->assertOk()->assertSee('Dolor al caminar');

        $request->update(['assigned_to' => $leader->id, 'status' => 'asignada']);
        $this->assertSame('asignada', $request->fresh()->status);

        $request->update(['status' => 'reconocida', 'acknowledged_at' => now()]);
        $this->assertNotNull($request->fresh()->acknowledged_at);

        $request->update(['response_text' => 'Vamos a ajustar tu plan de dolor.', 'responded_by' => $leader->id, 'responded_at' => now(), 'status' => 'respondida']);
        $this->assertTrue($request->fresh()->isAnswered());

        $request->update(['status' => 'resuelta', 'resolved_at' => now()]);
        $this->assertSame('resuelta', $request->fresh()->status);
    }
}
