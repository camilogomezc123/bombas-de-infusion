<?php

namespace Tests\Feature\Sepsis;

use App\Enums\CaseStatus;
use App\Enums\ProgramRole;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\ProgramMember;
use App\Models\SepsisCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepsisCaseAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditor_can_update_only_an_assigned_editable_case(): void
    {
        $auditor = User::factory()->create();
        $other = User::factory()->create();
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $auditor->id,
            'role' => ProgramRole::Auditor,
            'is_active' => true,
        ]);
        $patient = Patient::query()->create([
            'identification' => 'TEST-SEPSIS-AUTH-001',
            'full_name' => 'Persona Ficticia',
        ]);
        $assigned = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'assigned_auditor_id' => $auditor->id,
            'status' => CaseStatus::Assigned,
        ]);
        $notAssigned = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'assigned_auditor_id' => $other->id,
            'status' => CaseStatus::Assigned,
        ]);

        $this->assertTrue($auditor->can('update', $assigned));
        $this->assertFalse($auditor->can('update', $notAssigned));

        $assigned->status = CaseStatus::Completed;
        $assigned->saveQuietly();

        $this->assertFalse($auditor->can('update', $assigned->fresh()));
    }
}
