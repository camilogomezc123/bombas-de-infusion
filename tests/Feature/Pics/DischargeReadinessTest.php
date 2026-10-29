<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Livewire\Portal\DischargeReadinessComponent;
use App\Models\ClinicalProgram;
use App\Models\DischargeReadinessCheck;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DischargeReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('DR-'), 'full_name' => 'Preparación alta']);

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

    public function test_staff_creates_check_and_items(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $this->actingAs($leader, 'web');

        $check = DischargeReadinessCheck::query()->create(['pics_case_id' => $case->id]);
        $check->items()->create(['topic' => 'medicamentos', 'staff_instructions' => 'Tomar cada 8 horas']);

        $this->assertSame(1, $check->items()->count());
    }

    public function test_patient_marks_an_item_reviewed(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $this->actingAs($leader, 'web');

        $patient = $case->patient;
        $patient->update(['email' => 'paciente-dr@test.com']);

        $check = DischargeReadinessCheck::query()->create(['pics_case_id' => $case->id]);
        $item = $check->items()->create(['topic' => 'signos_alarma']);

        $this->actingAs($patient, 'patient');

        Livewire::test(DischargeReadinessComponent::class)
            ->call('reviewItem', $item->id)
            ->assertHasNoErrors();

        $item->refresh();
        $this->assertSame(\App\Models\Patient::class, $item->reviewed_by_type);
        $this->assertSame($patient->id, $item->reviewed_by_id);
        $this->assertNotNull($item->reviewed_at);
    }

    public function test_staff_verifies_understanding(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $this->actingAs($leader, 'web');

        $check = DischargeReadinessCheck::query()->create(['pics_case_id' => $case->id]);
        $item = $check->items()->create(['topic' => 'cuidados_en_casa']);

        $item->update(['understood' => true, 'verified_by' => auth('web')->id(), 'verified_at' => now()]);

        $this->assertTrue($item->fresh()->understood);
        $this->assertSame($leader->id, $item->fresh()->verified_by);
    }

    public function test_readiness_summary_percentage(): void
    {
        $case = $this->makeCase();
        $check = DischargeReadinessCheck::query()->create(['pics_case_id' => $case->id]);

        $this->assertSame(0.0, $check->readinessSummary()['percentage']);

        $check->items()->create(['topic' => 'medicamentos', 'understood' => true]);
        $check->items()->create(['topic' => 'signos_alarma', 'understood' => false]);
        $check->refresh();

        $this->assertSame(50.0, $check->readinessSummary()['percentage']);
        $this->assertCount(1, $check->readinessSummary()['pending']);

        $check->items()->create(['topic' => 'citas_control', 'understood' => true]);
        $check->refresh();
        $this->assertNotSame(0.0, $check->readinessSummary()['percentage']);
    }

    public function test_resource_pages_render_for_staff(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $check = DischargeReadinessCheck::query()->create(['pics_case_id' => $case->id]);
        $check->items()->create(['topic' => 'medicamentos']);

        $this->actingAs($leader)
            ->get('/pics/discharge-readiness-checks')
            ->assertOk();

        $this->actingAs($leader)
            ->get("/pics/discharge-readiness-checks/{$check->id}/edit")
            ->assertOk();
    }

    public function test_case_isolation_between_checks(): void
    {
        $caseA = $this->makeCase();
        $caseB = $this->makeCase();

        $checkA = DischargeReadinessCheck::query()->create(['pics_case_id' => $caseA->id]);
        $checkB = DischargeReadinessCheck::query()->create(['pics_case_id' => $caseB->id]);

        $this->assertNotSame($checkA->id, $checkB->id);
        $this->assertSame($caseA->id, $checkA->fresh()->case->id);
    }
}
