<?php

namespace Tests\Feature\Posuci;

use App\Enums\ProgramRole;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\ClinicalProgram;
use App\Models\GoalProgressReport;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Livewire\Portal\DiaryComponent;
use App\Livewire\Portal\GoalsComponent;
use App\Models\RecoveryGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PosuciIteration1Test extends TestCase
{
    use RefreshDatabase;

    private function makeCaseWithCaregiver(string $identification = 'T-1'): array
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => $identification, 'full_name' => 'Paciente '.$identification]);
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
        $staff = User::factory()->create();
        $caregiver = Caregiver::query()->create([
            'name' => 'Cuidador '.$identification,
            'email' => "cuidador-{$identification}@test.com",
            'password' => Hash::make('secret123'),
        ]);
        $authorization = CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id,
            'caregiver_id' => $caregiver->id,
            'can_write_diary' => true,
            'authorized_by' => $staff->id,
            'authorized_at' => now(),
        ]);

        return compact('program', 'patient', 'case', 'staff', 'caregiver', 'authorization');
    }

    public function test_unauthenticated_portal_access_redirects_to_login_without_error(): void
    {
        $this->get('/portal')->assertRedirect(route('portal.login'));
        $this->get('/portal/diario')->assertRedirect(route('portal.login'));
    }

    public function test_caregiver_writes_diary_entry_and_authorized_patient_reads_it(): void
    {
        ['case' => $case, 'caregiver' => $caregiver, 'patient' => $patient] = $this->makeCaseWithCaregiver();

        $this->actingAs($caregiver, 'caregiver');
        Livewire::test(DiaryComponent::class)
            ->set('entry_date', now()->toDateString())
            ->set('content', 'Hoy caminó un poco más que ayer.')
            ->set('visible_to_patient', true)
            ->call('save');

        $this->assertDatabaseHas('diary_entries', [
            'pics_case_id' => $case->id,
            'authorable_type' => Caregiver::class,
            'authorable_id' => $caregiver->id,
            'content' => 'Hoy caminó un poco más que ayer.',
        ]);

        // El paciente, en OTRA sesión, lee la misma entrada (persistencia real).
        $this->actingAs($patient, 'patient')
            ->get('/portal/diario')
            ->assertOk()
            ->assertSee('Hoy caminó un poco más que ayer.');
    }

    public function test_diary_entry_not_visible_to_patient_is_hidden(): void
    {
        ['case' => $case, 'caregiver' => $caregiver, 'patient' => $patient] = $this->makeCaseWithCaregiver();

        $case->diaryEntries()->create([
            'authorable_type' => Caregiver::class,
            'authorable_id' => $caregiver->id,
            'entry_date' => now(),
            'content' => 'Nota privada del equipo, no apta para el paciente.',
            'visible_to_patient' => false,
        ]);

        $this->actingAs($patient, 'patient')
            ->get('/portal/diario')
            ->assertOk()
            ->assertDontSee('Nota privada del equipo');
    }

    public function test_patient_can_write_a_diary_entry_and_see_it_labeled_with_their_name(): void
    {
        ['case' => $case, 'patient' => $patient, 'caregiver' => $caregiver] = $this->makeCaseWithCaregiver();

        $this->actingAs($patient, 'patient');
        Livewire::test(DiaryComponent::class)
            ->assertSee('Escribir una entrada')
            ->set('entry_date', now()->toDateString())
            ->set('content', 'Hoy me sentí con más energía.')
            ->call('save')
            ->assertHasNoErrors();

        $entry = $case->diaryEntries()->firstOrFail();
        $this->assertSame(Patient::class, $entry->authorable_type);
        $this->assertSame($patient->id, $entry->authorable_id);
        $this->assertTrue($entry->visible_to_patient);
        $this->assertSame($patient->full_name, $entry->authorLabel());

        // El paciente ve su propia entrada, y el cuidador también (sin filtro de visibilidad).
        $this->actingAs($patient, 'patient')
            ->get('/portal/diario')
            ->assertOk()
            ->assertSee('Hoy me sentí con más energía.')
            ->assertSee($patient->full_name);

        $this->actingAs($caregiver, 'caregiver')
            ->get('/portal/diario')
            ->assertOk()
            ->assertSee('Hoy me sentí con más energía.');
    }

    public function test_caregiver_reports_progress_on_a_goal_assigned_by_the_professional(): void
    {
        ['case' => $case, 'caregiver' => $caregiver] = $this->makeCaseWithCaregiver();
        $leader = User::factory()->create();

        // El profesional asigna una meta (equivalente a crearla desde el panel /pics).
        $goal = RecoveryGoal::query()->create([
            'pics_case_id' => $case->id,
            'domain' => 'movilidad',
            'description' => 'Caminar 10 metros con andador',
            'status' => 'active',
            'responsible_user_id' => $leader->id,
            'created_by' => $leader->id,
        ]);

        // El cuidador reporta un avance desde el portal.
        $this->actingAs($caregiver, 'caregiver');
        Livewire::test(GoalsComponent::class)
            ->set('selectedGoalId', $goal->id)
            ->set('notes', 'Caminó los 10 metros sin apoyo extra.')
            ->call('save');

        $report = GoalProgressReport::query()->where('recovery_goal_id', $goal->id)->firstOrFail();
        $this->assertSame(Caregiver::class, $report->reporter_type);
        $this->assertSame($caregiver->id, $report->reporter_id);
        $this->assertFalse($report->isValidated());
    }

    public function test_professional_validates_a_pending_report_from_the_pics_panel(): void
    {
        ['case' => $case, 'caregiver' => $caregiver] = $this->makeCaseWithCaregiver();
        $leader = User::factory()->create();
        ProgramMember::query()->create([
            'clinical_program_id' => $case->clinical_program_id,
            'user_id' => $leader->id,
            'role' => ProgramRole::Leader,
        ]);

        $goal = RecoveryGoal::query()->create([
            'pics_case_id' => $case->id,
            'domain' => 'movilidad',
            'description' => 'Caminar 10 metros con andador',
            'status' => 'active',
            'responsible_user_id' => $leader->id,
            'created_by' => $leader->id,
        ]);
        $report = $goal->progressReports()->create([
            'reporter_type' => Caregiver::class,
            'reporter_id' => $caregiver->id,
            'reported_at' => now(),
            'notes' => 'Caminó los 10 metros sin apoyo extra.',
        ]);

        $this->actingAs($leader)
            ->get('/pics/seguimiento')
            ->assertOk()
            ->assertSee('Caminó los 10 metros');

        $report->update(['validated_by' => $leader->id, 'validated_at' => now(), 'validation_notes' => 'Avance correcto.']);
        $this->assertTrue($report->fresh()->isValidated());
        $this->assertSame($leader->id, $report->fresh()->validated_by);
    }

    public function test_caregiver_without_authorization_cannot_access_another_patients_case(): void
    {
        $dataA = $this->makeCaseWithCaregiver('A-1');
        $dataB = $this->makeCaseWithCaregiver('B-1');

        // El cuidador de B nunca fue autorizado sobre el caso de A: al entrar solo ve el suyo.
        $this->actingAs($dataB['caregiver'], 'caregiver')
            ->get('/portal')
            ->assertOk()
            ->assertSee($dataB['case']->case_number)
            ->assertDontSee($dataA['case']->case_number);
    }

    public function test_revoking_caregiver_authorization_removes_diary_write_access(): void
    {
        ['authorization' => $authorization, 'caregiver' => $caregiver, 'staff' => $staff] = $this->makeCaseWithCaregiver();

        $this->actingAs($caregiver, 'caregiver');
        Livewire::test(DiaryComponent::class)->assertSee('Escribir una entrada');

        $authorization->update(['revoked_by' => $staff->id, 'revoked_at' => now()]);

        $this->actingAs($caregiver, 'caregiver')
            ->get('/portal')
            ->assertOk()
            ->assertSee('Todavía no tienes un programa de seguimiento activo');
    }

    public function test_patient_cannot_access_the_professional_pics_panel(): void
    {
        ['patient' => $patient] = $this->makeCaseWithCaregiver();

        // El paciente no tiene cuenta "web" de staff: el guard del panel /pics lo rechaza.
        $this->actingAs($patient, 'patient')->get('/pics')->assertRedirect();
    }

    public function test_reporting_a_difficulty_records_the_structured_reason(): void
    {
        ['case' => $case, 'caregiver' => $caregiver] = $this->makeCaseWithCaregiver();
        $goal = RecoveryGoal::query()->create([
            'pics_case_id' => $case->id, 'domain' => 'movilidad', 'description' => 'Caminar',
            'status' => 'active',
        ]);

        $this->actingAs($caregiver, 'caregiver');
        Livewire::test(GoalsComponent::class)
            ->set('selectedGoalId', $goal->id)
            ->set('had_difficulty', true)
            ->set('difficulty_reason', 'dolor')
            ->call('save');

        $report = $goal->progressReports()->firstOrFail();
        $this->assertSame('dolor', $report->difficulty_reason);
        $this->assertNull($report->difficulty_reason_other);
    }
}
