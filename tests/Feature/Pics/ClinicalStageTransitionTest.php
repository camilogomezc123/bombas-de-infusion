<?php

namespace Tests\Feature\Pics;

use App\Enums\ClinicalStage;
use App\Enums\ProgramRole;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClinicalStageTransitionTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('CS-'), 'full_name' => 'Etapa clínica']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    private function staffWithRole(PicsCase $case, ProgramRole $role): User
    {
        $user = User::factory()->create();
        ProgramMember::query()->create([
            'clinical_program_id' => $case->clinical_program_id, 'user_id' => $user->id, 'role' => $role,
        ]);

        return $user;
    }

    public function test_case_starts_in_uci_with_a_started_at_timestamp(): void
    {
        $case = $this->makeCase();

        $this->assertSame(ClinicalStage::Uci, $case->clinical_stage);
        $this->assertNotNull($case->uci_started_at);
    }

    public function test_a_manager_can_walk_the_full_stage_sequence(): void
    {
        $case = $this->makeCase();
        $manager = $this->staffWithRole($case, ProgramRole::Leader);

        $this->actingAs($manager, 'web');

        $case->clinical_stage = ClinicalStage::Hospitalizacion;
        $case->save();
        $this->assertSame(ClinicalStage::Hospitalizacion, $case->fresh()->clinical_stage);
        $this->assertNotNull($case->fresh()->hospitalization_started_at);

        $case->clinical_stage = ClinicalStage::Egreso;
        $case->save();
        $case->refresh();
        $this->assertSame(ClinicalStage::Egreso, $case->clinical_stage);
        $this->assertNotNull($case->discharge_confirmed_at);
        $this->assertSame($manager->id, $case->discharge_confirmed_by);

        $case->clinical_stage = ClinicalStage::Seguimiento;
        $case->save();
        $this->assertSame(ClinicalStage::Seguimiento, $case->fresh()->clinical_stage);
        $this->assertNotNull($case->fresh()->followup_started_at);
    }

    public function test_a_non_manager_cannot_skip_a_stage(): void
    {
        $case = $this->makeCase();
        $nurse = $this->staffWithRole($case, ProgramRole::Nurse);

        $this->actingAs($nurse, 'web');

        $this->expectException(ValidationException::class);

        $case->clinical_stage = ClinicalStage::Egreso;
        $case->save();
    }

    public function test_only_manager_or_physician_can_confirm_discharge(): void
    {
        $case = $this->makeCase();
        $nurse = $this->staffWithRole($case, ProgramRole::Nurse);

        $this->actingAs($nurse, 'web');
        $case->clinical_stage = ClinicalStage::Hospitalizacion;
        $case->save();

        $this->expectException(ValidationException::class);
        $case->clinical_stage = ClinicalStage::Egreso;
        $case->save();
    }

    public function test_a_physician_can_confirm_discharge(): void
    {
        $case = $this->makeCase();
        $physician = $this->staffWithRole($case, ProgramRole::Physician);

        $this->actingAs($physician, 'web');
        $case->clinical_stage = ClinicalStage::Hospitalizacion;
        $case->save();

        $case->clinical_stage = ClinicalStage::Egreso;
        $case->save();

        $case->refresh();
        $this->assertSame(ClinicalStage::Egreso, $case->clinical_stage);
        $this->assertSame($physician->id, $case->discharge_confirmed_by);
    }

    public function test_only_manager_can_revert_the_clinical_stage(): void
    {
        $case = $this->makeCase();
        $manager = $this->staffWithRole($case, ProgramRole::Leader);

        $this->actingAs($manager, 'web');
        $case->clinical_stage = ClinicalStage::Hospitalizacion;
        $case->save();

        $physician = $this->staffWithRole($case, ProgramRole::Physician);
        $this->actingAs($physician, 'web');

        $this->expectException(ValidationException::class);
        $case->clinical_stage = ClinicalStage::Uci;
        $case->save();
    }
}
