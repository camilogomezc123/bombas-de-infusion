<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Livewire\Portal\HomeMonitoringComponent;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\ClinicalProgram;
use App\Models\HomeMonitoringReading;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class HomeMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('HM-'), 'full_name' => 'Monitoreo']);

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

    public function test_patient_can_record_a_reading(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'patient-hm@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient');

        Livewire::test(HomeMonitoringComponent::class)
            ->set('reading_type', 'spo2')
            ->set('value', '97')
            ->set('unit', '%')
            ->call('save')
            ->assertHasNoErrors();

        $reading = HomeMonitoringReading::query()->where('pics_case_id', $case->id)->firstOrFail();
        $this->assertSame(Patient::class, $reading->recorded_by_type);
        $this->assertSame($patient->id, $reading->recorded_by_id);
        $this->assertSame('97', $reading->value);
    }

    public function test_authorized_caregiver_can_record_a_reading(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $caregiver = Caregiver::query()->create(['name' => 'Cuidador', 'email' => 'cg-hm@test.com', 'password' => Hash::make('secret')]);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id,
            'authorized_by' => $leader->id, 'authorized_at' => now(),
        ]);

        $this->actingAs($caregiver, 'caregiver');

        Livewire::test(HomeMonitoringComponent::class)
            ->set('reading_type', 'blood_pressure')
            ->set('value', '120/80')
            ->call('save')
            ->assertHasNoErrors();

        $reading = HomeMonitoringReading::query()->where('pics_case_id', $case->id)->firstOrFail();
        $this->assertSame(Caregiver::class, $reading->recorded_by_type);
        $this->assertSame($caregiver->id, $reading->recorded_by_id);
    }

    public function test_readings_tab_renders_read_only_for_staff(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $case->homeMonitoringReadings()->create([
            'reading_type' => 'weight', 'value' => '70', 'unit' => 'kg', 'measured_at' => now(),
        ]);

        $this->actingAs($leader)
            ->get("/pics/pics-cases/{$case->id}")
            ->assertOk()
            ->assertSee('Monitoreo en casa');
    }

    public function test_a_caregiver_from_another_case_cannot_record_here(): void
    {
        $caseA = $this->makeCase();
        $caseB = $this->makeCase();
        $leader = $this->leaderFor($caseA);
        $this->leaderFor($caseB);

        $caregiverB = Caregiver::query()->create(['name' => 'Otro', 'email' => 'cg-hm2@test.com', 'password' => Hash::make('secret')]);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $caseB->id, 'caregiver_id' => $caregiverB->id,
            'authorized_by' => $leader->id, 'authorized_at' => now(),
        ]);

        $this->actingAs($caregiverB, 'caregiver');

        Livewire::test(HomeMonitoringComponent::class)
            ->set('reading_type', 'spo2')
            ->set('value', '95')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(0, $caseA->homeMonitoringReadings()->count());
        $this->assertSame(1, $caseB->homeMonitoringReadings()->count());
    }
}
