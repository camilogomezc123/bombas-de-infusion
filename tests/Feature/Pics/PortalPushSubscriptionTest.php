<?php

namespace Tests\Feature\Pics;

use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\PushSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class PortalPushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('PUSH-'), 'full_name' => 'Suscripción push']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_patient_can_subscribe_to_push_notifications(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'push-patient@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->postJson('/portal/notificaciones/suscribir', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
                'keys' => ['p256dh' => 'fake-public-key', 'auth' => 'fake-auth-token'],
            ])
            ->assertOk();

        $subscription = PushSubscription::query()->where('endpoint', 'https://fcm.googleapis.com/fcm/send/abc123')->firstOrFail();
        $this->assertSame(Patient::class, $subscription->subscriber_type);
        $this->assertSame($patient->id, $subscription->subscriber_id);
        $this->assertSame('fake-public-key', $subscription->public_key);
    }

    public function test_subscribing_twice_with_the_same_endpoint_updates_instead_of_duplicating(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'push-patient2@test.com', 'must_change_password' => false]);

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/same-endpoint',
            'keys' => ['p256dh' => 'key-1', 'auth' => 'auth-1'],
        ];

        $this->actingAs($patient, 'patient')->postJson('/portal/notificaciones/suscribir', $payload)->assertOk();

        $payload['keys']['p256dh'] = 'key-2';
        $this->actingAs($patient, 'patient')->postJson('/portal/notificaciones/suscribir', $payload)->assertOk();

        $this->assertSame(1, PushSubscription::query()->where('endpoint', $payload['endpoint'])->count());
        $this->assertSame('key-2', PushSubscription::query()->where('endpoint', $payload['endpoint'])->first()->public_key);
    }

    public function test_resubscribing_with_an_endpoint_owned_by_another_actor_reassigns_it(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $caseA = $this->makeCase();
        $patientA = $caseA->patient;
        $patientA->update(['email' => 'push-shared-a@test.com', 'must_change_password' => false]);

        $caseB = $this->makeCase();
        $patientB = $caseB->patient;
        $patientB->update(['email' => 'push-shared-b@test.com', 'must_change_password' => false]);

        $sharedEndpoint = 'https://fcm.googleapis.com/fcm/send/shared-device';

        // Un tablet familiar compartido: primero se suscribe el paciente A...
        $this->actingAs($patientA, 'patient')
            ->postJson('/portal/notificaciones/suscribir', [
                'endpoint' => $sharedEndpoint,
                'keys' => ['p256dh' => 'key-a', 'auth' => 'auth-a'],
            ])->assertOk();

        // ...y luego, en el mismo navegador, se suscribe el paciente B.
        $this->actingAs($patientB, 'patient')
            ->postJson('/portal/notificaciones/suscribir', [
                'endpoint' => $sharedEndpoint,
                'keys' => ['p256dh' => 'key-b', 'auth' => 'auth-b'],
            ])->assertOk();

        $this->assertSame(1, PushSubscription::query()->where('endpoint', $sharedEndpoint)->count());
        $subscription = PushSubscription::query()->where('endpoint', $sharedEndpoint)->firstOrFail();
        $this->assertSame($patientB->id, $subscription->subscriber_id);
        $this->assertSame('key-b', $subscription->public_key);
    }

    public function test_patient_can_unsubscribe(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'push-patient3@test.com', 'must_change_password' => false]);

        $patient->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/to-remove',
            'public_key' => 'k', 'auth_token' => 'a',
        ]);

        $this->actingAs($patient, 'patient')
            ->postJson('/portal/notificaciones/desuscribir', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/to-remove'])
            ->assertOk();

        $this->assertSame(0, PushSubscription::query()->where('endpoint', 'https://fcm.googleapis.com/fcm/send/to-remove')->count());
    }

    public function test_unauthenticated_visitor_cannot_subscribe(): void
    {
        $this->postJson('/portal/notificaciones/suscribir', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/nope',
            'keys' => ['p256dh' => 'k', 'auth' => 'a'],
        ])->assertRedirect();
    }
}
