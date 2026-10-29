<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Livewire\Portal\CalendarComponent;
use App\Livewire\Portal\DiaryComponent;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PersonalReminder;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use App\Notifications\PatientNotActiveTodayNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El sistema ya permitía varios cuidadores por caso a nivel de datos y de
 * autorización (CaregiverAuthorization es una fila por cuidador, CaseAccess siempre
 * revisa la autorización del cuidador específico, nunca asume "el" cuidador) — estas
 * pruebas lo confirman de punta a punta con dos cuidadores reales en el mismo caso,
 * algo que no se había probado explícitamente antes.
 */
class MultipleCaregiversPerCaseTest extends TestCase
{
    use RefreshDatabase;

    private function leaderFor(int $programId): User
    {
        $leader = User::factory()->create();
        ProgramMember::query()->create(['clinical_program_id' => $programId, 'user_id' => $leader->id, 'role' => ProgramRole::Leader]);

        return $leader;
    }

    private function makeCaseWithTwoCaregivers(): array
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('MULTI-'), 'full_name' => 'Varios cuidadores']);
        $patient->update(['email' => uniqid('multi-patient-').'@test.com', 'password' => Hash::make('secret'), 'must_change_password' => false]);
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
        $leader = $this->leaderFor($program->id);

        $caregiverA = Caregiver::query()->create(['name' => 'Esposa', 'email' => uniqid('multi-a-').'@test.com', 'password' => Hash::make('secret')]);
        $caregiverB = Caregiver::query()->create(['name' => 'Hija', 'email' => uniqid('multi-b-').'@test.com', 'password' => Hash::make('secret')]);

        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiverA->id, 'authorized_by' => $leader->id, 'authorized_at' => now(),
        ]);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiverB->id, 'authorized_by' => $leader->id, 'authorized_at' => now(),
        ]);

        return compact('case', 'patient', 'caregiverA', 'caregiverB', 'leader');
    }

    public function test_both_caregivers_can_access_the_same_case_independently(): void
    {
        ['case' => $case, 'caregiverA' => $caregiverA] = $this->makeCaseWithTwoCaregivers();

        $this->actingAs($caregiverA, 'caregiver')
            ->get('/portal')
            ->assertOk()
            ->assertSee($case->case_number);
    }

    public function test_caregiver_authorization_preserves_the_care_context(): void
    {
        ['case' => $case, 'caregiverA' => $caregiver] = $this->makeCaseWithTwoCaregivers();

        $authorization = CaregiverAuthorization::query()
            ->whereBelongsTo($case, 'case')
            ->whereBelongsTo($caregiver)
            ->firstOrFail();

        $authorization->update([
            'relationship' => 'Esposa',
            'is_primary' => true,
            'is_emergency_contact' => true,
            'participates_in_decisions' => true,
            'preferred_language' => 'Español',
            'support_level' => 'permanente',
            'communication_notes' => 'Prefiere explicaciones breves y pausadas.',
        ]);

        $authorization->refresh();

        $this->assertTrue($authorization->is_primary);
        $this->assertTrue($authorization->is_emergency_contact);
        $this->assertTrue($authorization->participates_in_decisions);
        $this->assertSame('Español', $authorization->preferred_language);
        $this->assertSame('permanente', $authorization->support_level);
        $this->assertSame('Prefiere explicaciones breves y pausadas.', $authorization->communication_notes);
    }

    public function test_both_caregivers_can_access_the_same_case_independently_b(): void
    {
        ['case' => $case, 'caregiverB' => $caregiverB] = $this->makeCaseWithTwoCaregivers();

        $this->actingAs($caregiverB, 'caregiver')
            ->get('/portal')
            ->assertOk()
            ->assertSee($case->case_number);
    }

    public function test_diary_is_shared_between_both_caregivers_with_correct_authorship(): void
    {
        ['caregiverA' => $caregiverA] = $this->makeCaseWithTwoCaregivers();

        $this->actingAs($caregiverA, 'caregiver');
        Livewire::test(DiaryComponent::class)
            ->set('entry_date', now()->toDateString())
            ->set('content', 'La mamá durmió bien anoche.')
            ->call('save')
            ->assertHasNoErrors();

        $entry = \App\Models\DiaryEntry::query()->firstOrFail();
        $this->assertSame('Esposa', $entry->authorLabel());
    }

    public function test_caregiver_b_sees_the_diary_entry_written_by_caregiver_a(): void
    {
        ['caregiverA' => $caregiverA, 'caregiverB' => $caregiverB] = $this->makeCaseWithTwoCaregivers();

        $this->actingAs($caregiverA, 'caregiver');
        Livewire::test(DiaryComponent::class)
            ->set('entry_date', now()->toDateString())
            ->set('content', 'La mamá durmió bien anoche.')
            ->call('save')
            ->assertHasNoErrors();

        $this->actingAs($caregiverB, 'caregiver')
            ->get('/portal/diario')
            ->assertOk()
            ->assertSee('La mamá durmió bien anoche.')
            ->assertSee('Esposa');
    }

    public function test_personal_reminders_stay_private_between_two_caregivers_on_the_same_case(): void
    {
        ['case' => $case, 'caregiverA' => $caregiverA, 'caregiverB' => $caregiverB] = $this->makeCaseWithTwoCaregivers();

        PersonalReminder::query()->create([
            'pics_case_id' => $case->id, 'title' => 'Recordatorio de la esposa', 'remind_at' => now()->addDay(),
            'created_by_type' => Caregiver::class, 'created_by_id' => $caregiverA->id,
        ]);
        PersonalReminder::query()->create([
            'pics_case_id' => $case->id, 'title' => 'Recordatorio de la hija', 'remind_at' => now()->addDay(),
            'created_by_type' => Caregiver::class, 'created_by_id' => $caregiverB->id,
        ]);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $this->actingAs($caregiverA, 'caregiver');
        $eventsA = collect($this->getJson("/portal/calendario/eventos?start={$start}&end={$end}")->json())->pluck('title');
        $this->assertTrue($eventsA->contains(fn ($t) => str_contains($t, 'Recordatorio de la esposa')));
        $this->assertFalse($eventsA->contains(fn ($t) => str_contains($t, 'Recordatorio de la hija')));
    }

    public function test_personal_reminders_stay_private_for_the_other_caregiver_too(): void
    {
        ['case' => $case, 'caregiverA' => $caregiverA, 'caregiverB' => $caregiverB] = $this->makeCaseWithTwoCaregivers();

        PersonalReminder::query()->create([
            'pics_case_id' => $case->id, 'title' => 'Recordatorio de la esposa', 'remind_at' => now()->addDay(),
            'created_by_type' => Caregiver::class, 'created_by_id' => $caregiverA->id,
        ]);
        PersonalReminder::query()->create([
            'pics_case_id' => $case->id, 'title' => 'Recordatorio de la hija', 'remind_at' => now()->addDay(),
            'created_by_type' => Caregiver::class, 'created_by_id' => $caregiverB->id,
        ]);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $this->actingAs($caregiverB, 'caregiver');
        $eventsB = collect($this->getJson("/portal/calendario/eventos?start={$start}&end={$end}")->json())->pluck('title');
        $this->assertTrue($eventsB->contains(fn ($t) => str_contains($t, 'Recordatorio de la hija')));
        $this->assertFalse($eventsB->contains(fn ($t) => str_contains($t, 'Recordatorio de la esposa')));
    }

    public function test_inactivity_nudge_notifies_both_caregivers_independently(): void
    {
        Notification::fake();

        ['caregiverA' => $caregiverA, 'caregiverB' => $caregiverB] = $this->makeCaseWithTwoCaregivers();

        $this->artisan('agora:notify-caregiver-of-inactive-patient-today')->assertExitCode(0);

        Notification::assertSentTo($caregiverA, PatientNotActiveTodayNotification::class);
        Notification::assertSentTo($caregiverB, PatientNotActiveTodayNotification::class);
    }
}
