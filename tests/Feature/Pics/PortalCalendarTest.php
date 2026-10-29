<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Livewire\Portal\CalendarComponent;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\ClinicalProgram;
use App\Models\MedicationReconciliation;
use App\Models\Patient;
use App\Models\PersonalReminder;
use App\Models\PicsAgendaItem;
use App\Models\PicsCase;
use App\Models\PicsReferral;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PortalCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('CAL-'), 'full_name' => 'Calendario']);

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

    public function test_events_endpoint_returns_agenda_items_referrals_and_scheduled_medications(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'cal-patient@test.com', 'must_change_password' => false]);

        PicsAgendaItem::query()->create([
            'pics_case_id' => $case->id, 'type' => 'terapia', 'title' => 'Fisioterapia', 'status' => 'pendiente',
            'scheduled_at' => now()->addDays(3),
        ]);
        PicsReferral::query()->create([
            'pics_case_id' => $case->id, 'specialty' => 'Neumología', 'status' => 'scheduled', 'scheduled_at' => now()->addDays(5),
        ]);

        $reconciliation = MedicationReconciliation::query()->create(['pics_case_id' => $case->id]);
        $reconciliation->items()->create([
            'medication_name' => 'Enalapril', 'status' => 'continua', 'schedule_times' => ['08:00', '20:00'],
        ]);
        // Medicamento sin horarios: no debe generar eventos.
        $reconciliation->items()->create(['medication_name' => 'Sin horario', 'status' => 'continua']);

        $this->actingAs($patient, 'patient');

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->getJson("/portal/calendario/eventos?start={$start}&end={$end}")->assertOk();
        $events = $response->json();

        $titles = collect($events)->pluck('title');
        $this->assertTrue($titles->contains(fn ($t) => str_contains($t, 'Fisioterapia')));
        $this->assertTrue($titles->contains(fn ($t) => str_contains($t, 'Remisión: Neumología')));
        $this->assertGreaterThanOrEqual(2, $titles->filter(fn ($t) => str_contains($t, 'Enalapril'))->count());
        $this->assertFalse($titles->contains(fn ($t) => str_contains($t, 'Sin horario')));
    }

    private function makeCaseWithCaregiver(): array
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $patient = $case->patient;
        $patient->update(['email' => uniqid('cal-patient-').'@test.com', 'must_change_password' => false]);
        $caregiver = Caregiver::query()->create(['name' => 'Cuidador', 'email' => uniqid('cal-cg-').'@test.com', 'password' => Hash::make('secret')]);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id, 'authorized_by' => $leader->id, 'authorized_at' => now(),
        ]);

        PersonalReminder::query()->create([
            'pics_case_id' => $case->id, 'title' => 'Recordatorio del paciente', 'remind_at' => now()->addDay(),
            'created_by_type' => Patient::class, 'created_by_id' => $patient->id,
        ]);
        PersonalReminder::query()->create([
            'pics_case_id' => $case->id, 'title' => 'Recordatorio del cuidador', 'remind_at' => now()->addDay(),
            'created_by_type' => Caregiver::class, 'created_by_id' => $caregiver->id,
        ]);

        return compact('case', 'patient', 'caregiver');
    }

    public function test_patient_only_sees_their_own_personal_reminders(): void
    {
        ['patient' => $patient] = $this->makeCaseWithCaregiver();

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $this->actingAs($patient, 'patient');
        $patientEvents = collect($this->getJson("/portal/calendario/eventos?start={$start}&end={$end}")->assertOk()->json())->pluck('title');
        $this->assertTrue($patientEvents->contains(fn ($t) => str_contains($t, 'Recordatorio del paciente')));
        $this->assertFalse($patientEvents->contains(fn ($t) => str_contains($t, 'Recordatorio del cuidador')));
    }

    public function test_caregiver_only_sees_their_own_personal_reminders(): void
    {
        ['caregiver' => $caregiver] = $this->makeCaseWithCaregiver();

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $this->actingAs($caregiver, 'caregiver');
        $caregiverEvents = collect($this->getJson("/portal/calendario/eventos?start={$start}&end={$end}")->assertOk()->json())->pluck('title');
        $this->assertTrue($caregiverEvents->contains(fn ($t) => str_contains($t, 'Recordatorio del cuidador')));
        $this->assertFalse($caregiverEvents->contains(fn ($t) => str_contains($t, 'Recordatorio del paciente')));
    }

    public function test_adding_a_reminder_attributes_it_to_the_authenticated_actor(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'cal-patient3@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient');

        Livewire::test(CalendarComponent::class)
            ->set('title', 'Tomar agua')
            ->set('remind_at', now()->addHour()->format('Y-m-d\TH:i'))
            ->call('addReminder')
            ->assertHasNoErrors();

        $reminder = PersonalReminder::query()->where('pics_case_id', $case->id)->firstOrFail();
        $this->assertSame(Patient::class, $reminder->created_by_type);
        $this->assertSame($patient->id, $reminder->created_by_id);
        $this->assertSame('Tomar agua', $reminder->title);
    }

    public function test_events_expose_type_for_agenda_items_and_id_for_personal_reminders(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'cal-patient4@test.com', 'must_change_password' => false]);

        PicsAgendaItem::query()->create([
            'pics_case_id' => $case->id, 'type' => 'terapia', 'title' => 'Fisioterapia', 'status' => 'pendiente',
            'scheduled_at' => now()->addDays(2),
        ]);
        $reminder = PersonalReminder::query()->create([
            'pics_case_id' => $case->id, 'title' => 'Tomar agua', 'remind_at' => now()->addDay(),
            'created_by_type' => Patient::class, 'created_by_id' => $patient->id,
        ]);

        $this->actingAs($patient, 'patient');
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();
        $events = collect($this->getJson("/portal/calendario/eventos?start={$start}&end={$end}")->assertOk()->json());

        $therapy = $events->firstWhere(fn ($e) => str_contains($e['title'], 'Fisioterapia'));
        $this->assertSame('terapia', $therapy['extendedProps']['type']);

        $reminderEvent = $events->firstWhere(fn ($e) => str_contains($e['title'], 'Tomar agua'));
        $this->assertSame($reminder->id, $reminderEvent['extendedProps']['id']);
        $this->assertTrue($reminderEvent['editable']);
    }

    public function test_saving_a_reminder_creates_or_updates_it_for_the_actor(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'cal-patient5@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient');

        Livewire::test(CalendarComponent::class)
            ->call('saveReminder', null, 'Tomar agua', now()->addHour()->format('Y-m-d\TH:i'), null)
            ->assertHasNoErrors();

        $reminder = PersonalReminder::query()->where('pics_case_id', $case->id)->firstOrFail();
        $this->assertSame('Tomar agua', $reminder->title);
        $this->assertSame(Patient::class, $reminder->created_by_type);
        $this->assertSame($patient->id, $reminder->created_by_id);

        Livewire::test(CalendarComponent::class)
            ->call('saveReminder', $reminder->id, 'Tomar agua y estirar', now()->addHours(2)->format('Y-m-d\TH:i'), 'Nota nueva')
            ->assertHasNoErrors();

        $reminder->refresh();
        $this->assertSame('Tomar agua y estirar', $reminder->title);
        $this->assertSame('Nota nueva', $reminder->notes);
    }

    public function test_deleting_a_reminder_is_scoped_to_its_owner(): void
    {
        ['case' => $case, 'patient' => $patient, 'caregiver' => $caregiver] = $this->makeCaseWithCaregiver();
        $caregiverReminder = PersonalReminder::query()->where('created_by_type', Caregiver::class)->firstOrFail();

        $this->actingAs($patient, 'patient');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(CalendarComponent::class)->call('deleteReminder', $caregiverReminder->id);
    }

    public function test_rescheduling_a_reminder_updates_its_time(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'cal-patient6@test.com', 'must_change_password' => false]);
        $reminder = PersonalReminder::query()->create([
            'pics_case_id' => $case->id, 'title' => 'Tomar agua', 'remind_at' => now()->addDay(),
            'created_by_type' => Patient::class, 'created_by_id' => $patient->id,
        ]);

        $newTime = now()->addDays(3)->startOfHour();

        $this->actingAs($patient, 'patient');
        Livewire::test(CalendarComponent::class)
            ->call('rescheduleReminder', $reminder->id, $newTime->toIso8601String())
            ->assertHasNoErrors();

        $this->assertTrue($newTime->equalTo($reminder->fresh()->remind_at));
    }

    public function test_case_isolation_for_calendar_events(): void
    {
        $caseA = $this->makeCase();
        $caseB = $this->makeCase();

        PicsAgendaItem::query()->create([
            'pics_case_id' => $caseA->id, 'type' => 'cita', 'title' => 'Cita del caso A', 'status' => 'pendiente',
            'scheduled_at' => now()->addDays(2),
        ]);
        PicsAgendaItem::query()->create([
            'pics_case_id' => $caseB->id, 'type' => 'cita', 'title' => 'Cita del caso B', 'status' => 'pendiente',
            'scheduled_at' => now()->addDays(2),
        ]);

        $patientA = $caseA->patient;
        $patientA->update(['email' => 'cal-a@test.com', 'must_change_password' => false]);

        $this->actingAs($patientA, 'patient');
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();
        $titles = collect($this->getJson("/portal/calendario/eventos?start={$start}&end={$end}")->json())->pluck('title');

        $this->assertTrue($titles->contains(fn ($t) => str_contains($t, 'Cita del caso A')));
        $this->assertFalse($titles->contains(fn ($t) => str_contains($t, 'Cita del caso B')));
    }
}
