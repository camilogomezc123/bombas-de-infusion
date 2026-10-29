<?php

namespace Tests\Feature\Pics;

use App\Livewire\Portal\NotificationCenterComponent;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\SupportRequest;
use App\Notifications\SupportRequestAnsweredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('NOTIF-'), 'full_name' => 'Centro de notificaciones']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_patient_sees_a_notification_after_the_team_answers_their_request(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'notif-patient@test.com', 'must_change_password' => false]);

        $request = SupportRequest::query()->create([
            'pics_case_id' => $case->id, 'type' => 'dificultad', 'description' => 'Me duele al caminar',
            'priority' => 'media', 'status' => 'nueva', 'created_by_type' => Patient::class, 'created_by_id' => $patient->id,
        ]);

        $request->update(['response_text' => 'Vamos a ajustar tu terapia.', 'status' => 'respondida', 'responded_at' => now()]);
        $patient->notify(new SupportRequestAnsweredNotification($request));

        $this->actingAs($patient, 'patient');
        Livewire::test(NotificationCenterComponent::class)
            ->assertSee('respondió tu solicitud')
            ->assertSee('Vamos a ajustar tu terapia');

        $this->assertSame(1, $patient->fresh()->unreadNotifications()->count());
    }

    public function test_mark_all_as_read_clears_the_unread_count(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'notif-patient2@test.com', 'must_change_password' => false]);

        $request = SupportRequest::query()->create([
            'pics_case_id' => $case->id, 'type' => 'dificultad', 'description' => 'Algo',
            'priority' => 'baja', 'status' => 'respondida', 'response_text' => 'Listo',
            'created_by_type' => Patient::class, 'created_by_id' => $patient->id,
        ]);
        $patient->notify(new SupportRequestAnsweredNotification($request));

        $this->assertSame(1, $patient->unreadNotifications()->count());

        $this->actingAs($patient, 'patient');
        Livewire::test(NotificationCenterComponent::class)->call('markAllAsRead');

        $this->assertSame(0, $patient->fresh()->unreadNotifications()->count());
    }

    public function test_notifications_are_private_to_the_actor_they_belong_to(): void
    {
        $caseA = $this->makeCase();
        $patientA = $caseA->patient;
        $patientA->update(['email' => 'notif-a@test.com', 'must_change_password' => false]);

        $caseB = $this->makeCase();
        $patientB = $caseB->patient;
        $patientB->update(['email' => 'notif-b@test.com', 'must_change_password' => false]);

        $requestA = SupportRequest::query()->create([
            'pics_case_id' => $caseA->id, 'type' => 'dificultad', 'description' => 'Solo de A',
            'priority' => 'baja', 'status' => 'respondida', 'response_text' => 'Respuesta A',
            'created_by_type' => Patient::class, 'created_by_id' => $patientA->id,
        ]);
        $patientA->notify(new SupportRequestAnsweredNotification($requestA));

        $this->actingAs($patientB, 'patient');
        Livewire::test(NotificationCenterComponent::class)->assertDontSee('Respuesta A');

        $this->assertSame(0, $patientB->unreadNotifications()->count());
    }
}
