<?php

namespace Tests\Feature\Sepsis;

use App\Enums\CaseStatus;
use App\Enums\EvidenceStatus;
use App\Enums\ProgramRole;
use App\Filament\Sepsis\Resources\ComplianceEvidence\ComplianceEvidenceResource;
use App\Filament\Sepsis\Resources\SepsisCases\SepsisCaseResource;
use App\Models\ClinicalProgram;
use App\Models\EvidenceDocument;
use App\Models\Patient;
use App\Models\ProgramMember;
use App\Models\SepsisCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepsisCaseHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_view_page_shows_the_permanent_header_summary(): void
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
            'identification' => 'TEST-HEADER-001',
            'full_name' => 'Paciente Encabezado',
        ]);
        $case = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'assigned_auditor_id' => $user->id,
            'status' => CaseStatus::Hospitalized,
            'activation_at' => now()->subHours(2),
            'antibiotic_at' => now()->subHour(),
            'septic_shock' => true,
        ]);

        $response = $this->actingAs($user)->get(SepsisCaseResource::getUrl('view', ['record' => $case], panel: 'sepsis'));

        $response->assertOk();
        $response->assertSee($case->case_number);
        $response->assertSee('Tiempo cero');
        $response->assertSee('Bundle de primera hora');
        $response->assertSee('Choque séptico');
    }

    public function test_compliance_evidence_list_loads_after_status_cast_fix(): void
    {
        $user = User::factory()->create();
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $user->id,
            'role' => ProgramRole::QualityManager,
            'is_active' => true,
        ]);
        EvidenceDocument::query()->create([
            'clinical_program_id' => $program->id,
            'title' => 'Evidencia de prueba',
            'evidence_type' => 'protocol',
            'status' => EvidenceStatus::Verified,
            'document_reference' => 'Repositorio/prueba',
        ]);

        $this->actingAs($user)
            ->get(ComplianceEvidenceResource::getUrl('index', panel: 'sepsis'))
            ->assertOk()
            ->assertSee('Verificada');
    }
}
