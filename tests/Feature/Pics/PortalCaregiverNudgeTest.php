<?php

namespace Tests\Feature\Pics;

use App\Enums\CaseStatus;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\User;
use App\Notifications\PatientNotActiveTodayNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PortalCaregiverNudgeTest extends TestCase
{
    use RefreshDatabase;

    private function makeAuthorization(array $patientAttrs = [], array $authAttrs = []): array
    {
        $lastLoginAt = array_key_exists('last_login_at', $patientAttrs) ? $patientAttrs['last_login_at'] : null;
        unset($patientAttrs['last_login_at']);

        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(array_merge([
            'identification' => uniqid('NUDGE-'), 'full_name' => 'Aviso familia',
            'email' => uniqid('nudge-p-').'@test.com', 'password' => 'secret', 'must_change_password' => false,
        ], $patientAttrs));

        if ($lastLoginAt) {
            $patient->forceFill(['last_login_at' => $lastLoginAt])->save();
        }
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
        $leader = User::factory()->create();
        $caregiver = Caregiver::query()->create(['name' => 'Cuidador', 'email' => uniqid('nudge-cg-').'@test.com', 'password' => 'secret']);
        $authorization = CaregiverAuthorization::query()->create(array_merge([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id, 'authorized_by' => $leader->id, 'authorized_at' => now(),
        ], $authAttrs));

        return compact('case', 'patient', 'caregiver', 'authorization');
    }

    public function test_notifies_caregiver_when_patient_has_never_logged_in(): void
    {
        Notification::fake();

        ['caregiver' => $caregiver] = $this->makeAuthorization();

        $this->artisan('agora:notify-caregiver-of-inactive-patient-today')->assertExitCode(0);

        Notification::assertSentTo($caregiver, PatientNotActiveTodayNotification::class);
    }

    public function test_does_not_notify_when_patient_logged_in_today(): void
    {
        Notification::fake();

        ['caregiver' => $caregiver] = $this->makeAuthorization(['last_login_at' => now()]);

        $this->artisan('agora:notify-caregiver-of-inactive-patient-today')->assertExitCode(0);

        Notification::assertNotSentTo($caregiver, PatientNotActiveTodayNotification::class);
    }

    public function test_does_not_notify_twice_the_same_day(): void
    {
        Notification::fake();

        ['caregiver' => $caregiver] = $this->makeAuthorization([], ['last_inactivity_nudge_at' => now()]);

        $this->artisan('agora:notify-caregiver-of-inactive-patient-today')->assertExitCode(0);

        Notification::assertNotSentTo($caregiver, PatientNotActiveTodayNotification::class);
    }

    public function test_notifies_again_the_next_day(): void
    {
        Notification::fake();

        ['caregiver' => $caregiver] = $this->makeAuthorization([], ['last_inactivity_nudge_at' => now()->subDay()->startOfDay()]);

        $this->artisan('agora:notify-caregiver-of-inactive-patient-today')->assertExitCode(0);

        Notification::assertSentTo($caregiver, PatientNotActiveTodayNotification::class);
    }

    public function test_does_not_notify_for_a_revoked_authorization(): void
    {
        Notification::fake();

        ['caregiver' => $caregiver] = $this->makeAuthorization([], ['revoked_at' => now()]);

        $this->artisan('agora:notify-caregiver-of-inactive-patient-today')->assertExitCode(0);

        Notification::assertNotSentTo($caregiver, PatientNotActiveTodayNotification::class);
    }

    public function test_does_not_notify_when_patient_has_no_portal_account(): void
    {
        Notification::fake();

        ['caregiver' => $caregiver] = $this->makeAuthorization(['password' => null]);

        $this->artisan('agora:notify-caregiver-of-inactive-patient-today')->assertExitCode(0);

        Notification::assertNotSentTo($caregiver, PatientNotActiveTodayNotification::class);
    }

    public function test_skips_completed_and_cancelled_cases(): void
    {
        Notification::fake();

        ['caregiver' => $caregiver, 'case' => $case] = $this->makeAuthorization();
        $case->update(['status' => CaseStatus::Completed]);

        $this->artisan('agora:notify-caregiver-of-inactive-patient-today')->assertExitCode(0);

        Notification::assertNotSentTo($caregiver, PatientNotActiveTodayNotification::class);
    }
}
