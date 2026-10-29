<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Livewire\Portal\CaregiverJourneyComponent;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\CaregiverJourneyStep;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CaregiverJourneyTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('CJ-'), 'full_name' => 'Ruta cuidador']);

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

    private function authorizeCaregiver(PicsCase $case, User $staff, bool $canAccessJourney = true): Caregiver
    {
        $caregiver = Caregiver::query()->create(['name' => 'Cuidador', 'email' => uniqid('cg-').'@test.com', 'password' => Hash::make('secret')]);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id,
            'authorized_by' => $staff->id, 'authorized_at' => now(),
            'can_access_journey' => $canAccessJourney,
        ]);

        return $caregiver;
    }

    public function test_staff_can_create_steps_via_the_relation_manager(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);

        $this->actingAs($leader)
            ->get("/pics/pics-cases/{$case->id}")
            ->assertOk()
            ->assertSee('Ruta del cuidador');
    }

    public function test_caregiver_without_journey_access_gets_403(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $caregiver = $this->authorizeCaregiver($case, $leader, canAccessJourney: false);

        $this->actingAs($caregiver, 'caregiver')
            ->get('/portal/ruta-cuidador')
            ->assertForbidden();
    }

    public function test_authorized_caregiver_can_mark_a_step_complete(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $caregiver = $this->authorizeCaregiver($case, $leader);
        $step = CaregiverJourneyStep::query()->create(['pics_case_id' => $case->id, 'title' => 'Aprender manejo de secreciones']);

        $this->actingAs($caregiver, 'caregiver');

        Livewire::test(CaregiverJourneyComponent::class)
            ->call('markComplete', $step->id)
            ->assertHasNoErrors();

        $step->refresh();
        $this->assertSame(Caregiver::class, $step->reported_by_type);
        $this->assertSame($caregiver->id, $step->reported_by_id);
        $this->assertNotNull($step->reported_at);
    }

    public function test_staff_confirms_a_completed_step(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $caregiver = $this->authorizeCaregiver($case, $leader);
        $step = CaregiverJourneyStep::query()->create([
            'pics_case_id' => $case->id, 'title' => 'Contacto de emergencia',
            'reported_by_type' => Caregiver::class, 'reported_by_id' => $caregiver->id, 'reported_at' => now(),
        ]);

        $this->actingAs($leader);
        $step->update(['confirmed_by' => auth('web')->id(), 'confirmed_at' => now()]);

        $this->assertSame($leader->id, $step->fresh()->confirmed_by);
        $this->assertTrue($step->fresh()->isConfirmed());
    }

    public function test_a_caregiver_cannot_touch_steps_from_another_case(): void
    {
        $caseA = $this->makeCase();
        $caseB = $this->makeCase();
        $leader = $this->leaderFor($caseA);
        $this->leaderFor($caseB);

        $caregiverA = $this->authorizeCaregiver($caseA, $leader);
        $stepB = CaregiverJourneyStep::query()->create(['pics_case_id' => $caseB->id, 'title' => 'Paso de otro caso']);

        $this->actingAs($caregiverA, 'caregiver');

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(CaregiverJourneyComponent::class)
            ->call('markComplete', $stepB->id);
    }
}
