<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use App\Notifications\PortalAccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CaregiverInvitationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('CI-'), 'full_name' => 'Invitación Test']);

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

    public function test_staff_can_open_the_caregiver_relation_manager_from_the_case(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);

        $this->actingAs($leader)
            ->get("/pics/pics-cases/{$case->id}")
            ->assertOk()
            ->assertSee('Familia y cuidadores autorizados');
    }

    public function test_inviting_a_caregiver_generates_a_temporary_password_and_sends_the_notification(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $caregiver = Caregiver::query()->create([
            'name' => 'Cuidador Invitado', 'email' => 'invitado@test.com', 'password' => Hash::make('placeholder'),
        ]);
        $authorization = CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id,
            'authorized_by' => $leader->id, 'authorized_at' => now(),
        ]);

        $oldPasswordHash = $caregiver->password;

        $caregiver->sendPortalInvitation();

        $caregiver->refresh();
        $this->assertNotSame($oldPasswordHash, $caregiver->password);
        $this->assertTrue($caregiver->must_change_password);
        Notification::assertSentTo($caregiver, PortalAccountInvitation::class);
    }

    public function test_revoking_removes_the_active_authorization(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $caregiver = Caregiver::query()->create(['name' => 'C', 'email' => 'rev@test.com', 'password' => Hash::make('x')]);
        $authorization = CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id,
            'authorized_by' => $leader->id, 'authorized_at' => now(),
        ]);

        $this->assertTrue($authorization->isActive());

        $authorization->update(['revoked_by' => $leader->id, 'revoked_at' => now()]);

        $this->assertFalse($authorization->fresh()->isActive());
    }

    public function test_configuring_patient_portal_access_sets_email_and_sends_invitation(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $this->assertNull($case->patient->email);

        $case->patient->update(['email' => 'nuevo.paciente@test.com']);
        $case->patient->sendPortalInvitation();

        $case->patient->refresh();
        $this->assertTrue($case->patient->must_change_password);
        $this->assertNotNull($case->patient->password);
        Notification::assertSentTo($case->patient, PortalAccountInvitation::class);
    }
}
