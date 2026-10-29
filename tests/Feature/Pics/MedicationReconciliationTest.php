<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Livewire\Portal\SupportRequestComponent;
use App\Models\ClinicalProgram;
use App\Models\MedicationReconciliation;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MedicationReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('MR-'), 'full_name' => 'Conciliación']);

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

    public function test_staff_creates_reconciliation_and_items(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $this->actingAs($leader, 'web');

        $reconciliation = MedicationReconciliation::query()->create(['pics_case_id' => $case->id, 'reconciled_by' => $leader->id, 'reconciled_at' => now()]);
        $reconciliation->items()->create(['medication_name' => 'Metformina', 'dose' => '850 mg', 'route' => 'oral', 'frequency' => 'Cada 12 horas', 'status' => 'continua']);
        $reconciliation->items()->create(['medication_name' => 'Midazolam', 'status' => 'suspendida', 'reconciliation_notes' => 'Solo se usó en UCI']);

        $this->assertSame(2, $reconciliation->items()->count());
    }

    public function test_resource_pages_render_for_staff(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        MedicationReconciliation::query()->create(['pics_case_id' => $case->id]);

        $this->actingAs($leader)
            ->get('/pics/medication-reconciliations')
            ->assertOk();
    }

    public function test_portal_shows_the_medication_list(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $reconciliation = MedicationReconciliation::query()->create(['pics_case_id' => $case->id]);
        $reconciliation->items()->create(['medication_name' => 'Enalapril', 'dose' => '10 mg', 'status' => 'nueva', 'patient_instructions' => 'Tomar en ayunas']);

        $patient = $case->patient;
        $patient->update(['email' => 'patient-mr@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal/medicamentos')
            ->assertOk()
            ->assertSee('Enalapril')
            ->assertSee('Tomar en ayunas');
    }

    public function test_reporting_a_doubt_creates_a_support_request_with_the_right_type(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'patient-mr2@test.com']);

        $this->actingAs($patient, 'patient');

        Livewire::withQueryParams(['tipo' => 'duda_medicamento', 'medicamento' => 'Enalapril'])
            ->test(SupportRequestComponent::class)
            ->assertSet('type', 'duda_medicamento')
            ->assertSet('description', 'Duda sobre Enalapril: ')
            ->set('description', 'Duda sobre Enalapril: ¿lo tomo con comida?')
            ->call('save')
            ->assertHasNoErrors();

        $request = SupportRequest::query()->where('pics_case_id', $case->id)->firstOrFail();
        $this->assertSame('duda_medicamento', $request->type);
        $this->assertStringContainsString('Enalapril', $request->description);
    }

    public function test_case_isolation_between_reconciliations(): void
    {
        $caseA = $this->makeCase();
        $caseB = $this->makeCase();

        $reconciliationA = MedicationReconciliation::query()->create(['pics_case_id' => $caseA->id]);
        $reconciliationB = MedicationReconciliation::query()->create(['pics_case_id' => $caseB->id]);

        $this->assertNotSame($reconciliationA->id, $reconciliationB->id);
        $this->assertSame($caseA->id, $reconciliationA->fresh()->case->id);
    }
}
