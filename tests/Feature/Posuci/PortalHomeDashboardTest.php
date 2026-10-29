<?php

namespace Tests\Feature\Posuci;

use App\Enums\ProgramRole;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\CaregiverJourneyStep;
use App\Models\ClinicalProgram;
use App\Models\EducationAssignment;
use App\Models\EducationResource;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalHomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('PH-'), 'full_name' => 'Inicio del portal']);

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

    public function test_home_shows_clinical_stage_and_all_patient_shortcuts(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'home-patient@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertSee('UCI')
            ->assertSee('Antes y ahora')
            ->assertSee('Medicamentos')
            ->assertSee('Monitoreo en casa')
            ->assertSee('Preparación para el alta')
            ->assertSee('Educación')
            ->assertDontSee('Mi ruta como cuidador');
    }

    public function test_home_summarizes_pending_items_across_modules(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'home-patient2@test.com', 'must_change_password' => false]);

        $resource = EducationResource::query()->create(['title' => 'Contenido pendiente', 'category' => 'general', 'is_active' => true]);
        $case->educationAssignments()->create(['education_resource_id' => $resource->id]);

        $case->supportRequests()->create([
            'type' => 'dificultad', 'description' => 'Sin respuesta todavía',
            'created_by_type' => Patient::class, 'created_by_id' => $patient->id,
        ]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertSee('1 contenido educativo sin leer')
            ->assertSee('1 solicitud esperando respuesta');
    }

    public function test_home_shows_caregiver_journey_pending_only_when_authorized(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $caregiver = Caregiver::query()->create(['name' => 'Cuidador', 'email' => 'home-cg@test.com', 'password' => Hash::make('secret')]);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id,
            'authorized_by' => $leader->id, 'authorized_at' => now(), 'can_access_journey' => true,
        ]);
        CaregiverJourneyStep::query()->create(['pics_case_id' => $case->id, 'title' => 'Paso pendiente']);

        $this->actingAs($caregiver, 'caregiver')
            ->get('/portal')
            ->assertOk()
            ->assertSee('Mi ruta como cuidador')
            ->assertSee('1 paso de tu ruta como cuidador sin completar');
    }

    public function test_home_hides_caregiver_journey_shortcut_when_not_authorized(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $caregiver = Caregiver::query()->create(['name' => 'Cuidador sin ruta', 'email' => 'home-cg2@test.com', 'password' => Hash::make('secret')]);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id,
            'authorized_by' => $leader->id, 'authorized_at' => now(), 'can_access_journey' => false,
        ]);

        $this->actingAs($caregiver, 'caregiver')
            ->get('/portal')
            ->assertOk()
            ->assertDontSee('Mi ruta como cuidador');
    }
}
