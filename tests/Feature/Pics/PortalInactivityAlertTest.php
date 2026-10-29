<?php

namespace Tests\Feature\Pics;

use App\Enums\CaseStatus;
use App\Enums\ProgramRole;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\ClinicalProgram;
use App\Models\DiaryEntry;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use App\Notifications\PortalInactivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PortalInactivityAlertTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('INA-'), 'full_name' => 'Inactividad']);

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

    public function test_notifies_staff_for_a_case_with_no_recent_portal_activity(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $auditor = User::factory()->create();
        $case->update(['assigned_auditor_id' => $auditor->id]);

        $this->artisan('agora:check-portal-inactivity')->assertExitCode(0);

        Notification::assertSentTo($auditor, PortalInactivityNotification::class);
        $this->assertNotNull($case->fresh()->last_inactivity_alert_at);
    }

    public function test_does_not_notify_a_case_with_recent_diary_activity(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $leader = $this->leaderFor($case);

        DiaryEntry::query()->create([
            'pics_case_id' => $case->id, 'content' => 'Hoy caminó un poco más.', 'entry_date' => today(),
            'authorable_type' => Patient::class, 'authorable_id' => $case->patient_id,
        ]);

        $this->artisan('agora:check-portal-inactivity')->assertExitCode(0);

        Notification::assertNotSentTo($leader, PortalInactivityNotification::class);
        $this->assertNull($case->fresh()->last_inactivity_alert_at);
    }

    public function test_does_not_notify_again_within_the_cooldown_window(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $case->update(['last_inactivity_alert_at' => now()->subDays(2)]);

        $this->artisan('agora:check-portal-inactivity')->assertExitCode(0);

        Notification::assertNotSentTo($leader, PortalInactivityNotification::class);
    }

    public function test_notifies_again_once_the_cooldown_has_passed(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $case->update(['last_inactivity_alert_at' => now()->subDays(8)]);

        $this->artisan('agora:check-portal-inactivity')->assertExitCode(0);

        Notification::assertSentTo($leader, PortalInactivityNotification::class);
    }

    public function test_skips_completed_and_cancelled_cases(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $case->update(['status' => CaseStatus::Completed]);

        $this->artisan('agora:check-portal-inactivity')->assertExitCode(0);

        Notification::assertNotSentTo($leader, PortalInactivityNotification::class);
    }

    public function test_flags_an_authorized_caregiver_who_has_never_logged_in(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $leader = $this->leaderFor($case);

        DiaryEntry::query()->create([
            'pics_case_id' => $case->id, 'content' => 'Actividad reciente del paciente.', 'entry_date' => today(),
            'authorable_type' => Patient::class, 'authorable_id' => $case->patient_id,
        ]);

        $caregiver = Caregiver::query()->create(['name' => 'Cuidador', 'email' => 'ina-cg@test.com', 'password' => 'secret']);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id, 'authorized_by' => $leader->id,
            'authorized_at' => now()->subDays(5),
        ]);

        $this->artisan('agora:check-portal-inactivity')->assertExitCode(0);

        Notification::assertSentTo($leader, PortalInactivityNotification::class, function ($notification) {
            return in_array('cuidador_sin_ingresar', $notification->reasons, true);
        });
    }
}
