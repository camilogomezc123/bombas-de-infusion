<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Livewire\Portal\EducationComponent;
use App\Models\ClinicalProgram;
use App\Models\EducationAssignment;
use App\Models\EducationResource;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EducationResourceTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('ED-'), 'full_name' => 'Educación']);

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

    public function test_staff_creates_catalog_content(): void
    {
        $resource = EducationResource::query()->create([
            'title' => 'Ejercicios de respiración', 'category' => 'respiratorio', 'audience' => 'paciente',
            'body' => 'Practica respiración diafragmática 10 minutos al día.',
        ]);

        $this->assertTrue($resource->fresh()->is_active);
    }

    public function test_staff_assigns_content_with_server_side_attribution(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $resource = EducationResource::query()->create(['title' => 'Nutrición post-UCI', 'category' => 'nutricion']);

        $this->actingAs($leader, 'web');
        $assignment = $case->educationAssignments()->create([
            'education_resource_id' => $resource->id,
            'assigned_by' => auth('web')->id(),
            'assigned_at' => now(),
        ]);

        $this->assertSame($leader->id, $assignment->assigned_by);
        $this->assertFalse($assignment->isViewed());
    }

    public function test_portal_shows_only_active_assigned_content(): void
    {
        $case = $this->makeCase();
        $activeResource = EducationResource::query()->create(['title' => 'Activo', 'category' => 'general', 'is_active' => true]);
        $inactiveResource = EducationResource::query()->create(['title' => 'Inactivo', 'category' => 'general', 'is_active' => false]);

        $case->educationAssignments()->create(['education_resource_id' => $activeResource->id]);
        $case->educationAssignments()->create(['education_resource_id' => $inactiveResource->id]);

        $patient = $case->patient;
        $patient->update(['email' => 'patient-ed@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal/educacion')
            ->assertOk()
            ->assertSee('Activo')
            ->assertDontSee('Inactivo');
    }

    public function test_patient_marks_content_as_viewed(): void
    {
        $case = $this->makeCase();
        $resource = EducationResource::query()->create(['title' => 'Movilidad', 'category' => 'movilidad']);
        $assignment = $case->educationAssignments()->create(['education_resource_id' => $resource->id]);

        $patient = $case->patient;
        $patient->update(['email' => 'patient-ed2@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient');

        Livewire::test(EducationComponent::class)
            ->call('markViewed', $assignment->id)
            ->assertHasNoErrors();

        $assignment->refresh();
        $this->assertSame(Patient::class, $assignment->viewed_by_type);
        $this->assertSame($patient->id, $assignment->viewed_by_id);
        $this->assertNotNull($assignment->viewed_at);
    }

    public function test_resource_and_case_tab_render_for_staff(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $resource = EducationResource::query()->create(['title' => 'Contenido de prueba', 'category' => 'general']);
        $case->educationAssignments()->create(['education_resource_id' => $resource->id]);

        $this->actingAs($leader)
            ->get('/pics/education-resources')
            ->assertOk()
            ->assertSee('Contenido de prueba');

        $this->actingAs($leader)
            ->get("/pics/pics-cases/{$case->id}")
            ->assertOk()
            ->assertSee('Educación personalizada');
    }

    public function test_case_isolation_between_assignments(): void
    {
        $caseA = $this->makeCase();
        $caseB = $this->makeCase();
        $resource = EducationResource::query()->create(['title' => 'Compartido', 'category' => 'general']);

        $assignmentA = $caseA->educationAssignments()->create(['education_resource_id' => $resource->id]);
        $caseB->educationAssignments()->create(['education_resource_id' => $resource->id]);

        $this->assertSame($caseA->id, $assignmentA->fresh()->case->id);
        $this->assertSame(1, EducationAssignment::query()->where('pics_case_id', $caseA->id)->count());
        $this->assertSame(1, EducationAssignment::query()->where('pics_case_id', $caseB->id)->count());
    }
}
