<?php

namespace Tests\Feature\Pics;

use App\Enums\CaseStatus;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Support\Posuci\WebPushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PortalDailyPushTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscribedPatient(): Patient
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('DPUSH-'), 'full_name' => 'Push diario']);
        PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);

        $patient->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/'.uniqid(),
            'public_key' => 'k', 'auth_token' => 'a',
        ]);

        return $patient;
    }

    public function test_does_nothing_when_vapid_keys_are_not_configured(): void
    {
        // phpunit.xml deja VAPID_PUBLIC_KEY/VAPID_PRIVATE_KEY vacías a propósito —
        // este test usa el WebPushSender real (sin mock) para probar ese camino.
        $this->makeSubscribedPatient();

        $this->artisan('agora:send-daily-portal-push')
            ->expectsOutputToContain('no están configuradas')
            ->assertExitCode(0);
    }

    public function test_sends_a_push_to_each_subscribed_patient_with_an_active_case(): void
    {
        $patient = $this->makeSubscribedPatient();

        $mock = Mockery::mock(WebPushSender::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('sendToActor')
            ->once()
            ->withArgs(fn ($actor) => $actor->is($patient))
            ->andReturn(1);
        $this->app->instance(WebPushSender::class, $mock);

        $this->artisan('agora:send-daily-portal-push')
            ->expectsOutputToContain('Notificaciones push enviadas: 1.')
            ->assertExitCode(0);
    }

    public function test_does_not_send_to_a_patient_with_a_completed_case(): void
    {
        $patient = $this->makeSubscribedPatient();
        PicsCase::query()->where('patient_id', $patient->id)->update(['status' => CaseStatus::Completed]);

        $mock = Mockery::mock(WebPushSender::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldNotReceive('sendToActor');
        $this->app->instance(WebPushSender::class, $mock);

        $this->artisan('agora:send-daily-portal-push')
            ->expectsOutputToContain('Notificaciones push enviadas: 0.')
            ->assertExitCode(0);
    }

    public function test_does_not_send_to_a_patient_without_a_subscription(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('DPUSH-'), 'full_name' => 'Sin suscripción']);
        PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);

        $mock = Mockery::mock(WebPushSender::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldNotReceive('sendToActor');
        $this->app->instance(WebPushSender::class, $mock);

        $this->artisan('agora:send-daily-portal-push')->assertExitCode(0);
    }
}
