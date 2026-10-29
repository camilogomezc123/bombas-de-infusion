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

class SepsisCaseAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_creation_and_changes_are_audited(): void
    {
        $user = User::factory()->create();
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $user->id,
            'role' => ProgramRole::Leader,
            'is_active' => true,
        ]);
        $patient = Patient::query()->create([
            'identification' => 'TEST-SEPSIS-AUDIT-001',
            'full_name' => 'Persona Ficticia',
        ]);

        $this->actingAs($user);

        $case = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'status' => CaseStatus::Hospitalized,
            'is_valid' => true,
        ]);
        $case->update(['infection_focus' => 'Respiratorio']);

        $this->assertDatabaseHas('clinical_audits', [
            'auditable_type' => SepsisCase::class,
            'auditable_id' => $case->id,
            'event' => 'created',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('clinical_audits', [
            'auditable_type' => SepsisCase::class,
            'auditable_id' => $case->id,
            'event' => 'updated',
            'field' => 'infection_focus',
            'old_value' => null,
            'new_value' => 'Respiratorio',
        ]);
    }
}
