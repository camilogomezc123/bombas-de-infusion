<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Livewire\Portal\CalendarComponent;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsAgendaItem;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use App\Notifications\AppointmentResponseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentResponseTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('AR-'), 'full_name' => 'Respuesta cita']);

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

    public function test_patient_can_confirm_an_appointment_and_notifies_the_assigned_auditor(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $auditor = User::factory()->create();
        $case->update(['assigned_auditor_id' => $auditor->id]);
        $patient = $case->patient;
        $patient->update(['email' => 'ar-patient@test.com', 'must_change_password' => false]);

        $item = PicsAgendaItem::query()->create([
            'pics_case_id' => $case->id, 'type' => 'cita', 'title' => 'Control', 'status' => 'pendiente',
            'scheduled_at' => now()->addDays(3),
        ]);

        $this->actingAs($patient, 'patient');

        Livewire::test(CalendarComponent::class)
            ->call('respondToAppointment', $item->id, 'confirmada')
            ->assertHasNoErrors();

        $item->refresh();
        $this->assertSame('confirmada', $item->patient_response);
        $this->assertSame(Patient::class, $item->patient_responded_by_type);
        $this->assertSame($patient->id, $item->patient_responded_by_id);
        $this->assertNotNull($item->patient_response_at);

        Notification::assertSentTo($auditor, AppointmentResponseNotification::class);
    }

    public function test_patient_can_decline_an_appointment_and_notifies_program_leaders_when_no_auditor(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $patient = $case->patient;
        $patient->update(['email' => 'ar-patient2@test.com', 'must_change_password' => false]);

        $item = PicsAgendaItem::query()->create([
            'pics_case_id' => $case->id, 'type' => 'terapia', 'title' => 'Fisioterapia', 'status' => 'pendiente',
            'scheduled_at' => now()->addDays(2),
        ]);

        $this->actingAs($patient, 'patient');

        Livewire::test(CalendarComponent::class)
            ->call('respondToAppointment', $item->id, 'no_asistira')
            ->assertHasNoErrors();

        $this->assertSame('no_asistira', $item->fresh()->patient_response);
        Notification::assertSentTo($leader, AppointmentResponseNotification::class);
    }

    public function test_non_respondable_types_cannot_be_responded_to(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'ar-patient3@test.com', 'must_change_password' => false]);

        $item = PicsAgendaItem::query()->create([
            'pics_case_id' => $case->id, 'type' => 'tarea', 'title' => 'Tarea interna', 'status' => 'pendiente',
            'scheduled_at' => now()->addDays(1),
        ]);

        $this->actingAs($patient, 'patient');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(CalendarComponent::class)
            ->call('respondToAppointment', $item->id, 'confirmada');
    }

    public function test_staff_sees_the_patient_response_in_the_case_agenda(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $item = PicsAgendaItem::query()->create([
            'pics_case_id' => $case->id, 'type' => 'cita', 'title' => 'Control staff', 'status' => 'pendiente',
            'scheduled_at' => now()->addDays(3), 'patient_response' => 'confirmada', 'patient_response_at' => now(),
        ]);

        $this->actingAs($leader)
            ->get("/pics/pics-cases/{$case->id}")
            ->assertOk()
            ->assertSee('Agenda coordinada');
    }
}
