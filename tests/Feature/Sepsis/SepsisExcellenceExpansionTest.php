<?php

namespace Tests\Feature\Sepsis;

use App\Enums\CaseStatus;
use App\Enums\EvidenceStatus;
use App\Enums\ProgramRole;
use App\Filament\Sepsis\Pages\Tracers;
use App\Filament\Sepsis\Resources\ComplianceEvidence\ComplianceEvidenceResource;
use App\Filament\Sepsis\Resources\Findings\FindingResource;
use App\Filament\Sepsis\Resources\ProgramDocuments\ProgramDocumentResource;
use App\Filament\Sepsis\Resources\ProgramResources\ProgramResourceResource;
use App\Filament\Sepsis\Resources\SafetyEvents\SafetyEventResource;
use App\Models\AccreditationChapter;
use App\Models\AccreditationFramework;
use App\Models\AccreditationStandard;
use App\Models\AssessmentFinding;
use App\Models\ClinicalAudit;
use App\Models\ClinicalProgram;
use App\Models\ComplianceAssessment;
use App\Models\EvidenceDocument;
use App\Models\MeasurableElement;
use App\Models\Patient;
use App\Models\ProgramMember;
use App\Models\SepsisBundleTask;
use App\Models\SepsisCase;
use App\Models\SepsisCulture;
use App\Models\SepsisSafetyEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SepsisExcellenceExpansionTest extends TestCase
{
    use RefreshDatabase;

    private function leaderUser(): User
    {
        $user = User::factory()->create();
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $user->id,
            'role' => ProgramRole::Leader,
            'is_active' => true,
        ]);

        return $user;
    }

    public function test_all_new_tables_exist(): void
    {
        foreach ([
            'program_resources', 'sepsis_screenings', 'sepsis_bundle_tasks',
            'sepsis_hemodynamic_assessments', 'sepsis_cultures',
            'sepsis_antimicrobial_administrations', 'sepsis_source_control_actions',
            'sepsis_care_transitions', 'sepsis_patient_education_records',
            'sepsis_safety_events',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Falta la tabla {$table}.");
        }

        $this->assertTrue(Schema::hasColumn('program_members', 'discipline'));
        $this->assertTrue(Schema::hasColumn('evidence_documents', 'replaces_evidence_document_id'));
        $this->assertTrue(Schema::hasColumn('sepsis_cases', 'time_zero_validated_at'));
    }

    /**
     * La reversibilidad completa del historial de migraciones (incluidas estas) ya se
     * verifica en ProgramFoundationTest::test_foundation_migrations_are_reversible
     * mediante un rollback real de Artisan, que es robusto al crecimiento del esquema.
     */
    public function test_non_member_is_denied_on_every_new_resource(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get(ProgramResourceResource::getUrl('index', panel: 'sepsis'))->assertForbidden();
        $this->actingAs($outsider)->get(ProgramDocumentResource::getUrl('index', panel: 'sepsis'))->assertForbidden();
        $this->actingAs($outsider)->get(SafetyEventResource::getUrl('index', panel: 'sepsis'))->assertForbidden();
        $this->actingAs($outsider)->get(FindingResource::getUrl('index', panel: 'sepsis'))->assertForbidden();
        $this->actingAs($outsider)->get(Tracers::getUrl(panel: 'sepsis'))->assertForbidden();
    }

    public function test_leader_can_reach_every_new_resource(): void
    {
        $leader = $this->leaderUser();

        $this->actingAs($leader)->get(ProgramResourceResource::getUrl('index', panel: 'sepsis'))->assertOk();
        $this->actingAs($leader)->get(ProgramDocumentResource::getUrl('index', panel: 'sepsis'))->assertOk();
        $this->actingAs($leader)->get(SafetyEventResource::getUrl('index', panel: 'sepsis'))->assertOk();
        $this->actingAs($leader)->get(FindingResource::getUrl('index', panel: 'sepsis'))->assertOk();
        $this->actingAs($leader)->get(Tracers::getUrl(panel: 'sepsis'))->assertOk();
    }

    public function test_bundle_task_and_culture_changes_are_audited_like_the_case(): void
    {
        $leader = $this->leaderUser();
        $patient = Patient::query()->create(['identification' => 'TEST-EXP-001', 'full_name' => 'Paciente Expansión']);
        $case = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'assigned_auditor_id' => $leader->id,
            'status' => CaseStatus::Hospitalized,
            'activation_at' => now()->subHours(2),
        ]);

        $task = SepsisBundleTask::query()->create([
            'sepsis_case_id' => $case->id,
            'task_key' => 'antibiotic',
            'label' => 'Antibiótico',
            'status' => 'pending',
        ]);
        $task->update(['status' => 'done', 'done_at' => now()]);

        $culture = SepsisCulture::query()->create(['sepsis_case_id' => $case->id, 'site' => 'Sangre']);

        $this->assertGreaterThanOrEqual(2, ClinicalAudit::query()
            ->where('auditable_type', $task->getMorphClass())->where('auditable_id', $task->id)->count());
        $this->assertSame(1, ClinicalAudit::query()
            ->where('auditable_type', $culture->getMorphClass())->where('auditable_id', $culture->id)->count());
    }

    public function test_guides_and_compliance_evidence_do_not_duplicate_the_same_document(): void
    {
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        $guideline = EvidenceDocument::query()->create([
            'clinical_program_id' => $program->id,
            'title' => 'Guía clínica de prueba',
            'evidence_type' => 'clinical_guideline',
            'status' => EvidenceStatus::Verified,
            'document_reference' => 'testing/guia.pdf',
        ]);
        $auditEvidence = EvidenceDocument::query()->create([
            'clinical_program_id' => $program->id,
            'title' => 'Auditoría de prueba',
            'evidence_type' => 'audit',
            'status' => EvidenceStatus::Verified,
            'document_reference' => 'testing/auditoria.pdf',
        ]);

        $this->assertTrue(ProgramDocumentResource::getEloquentQuery()->whereKey($guideline->id)->exists());
        $this->assertFalse(ProgramDocumentResource::getEloquentQuery()->whereKey($auditEvidence->id)->exists());

        $this->assertTrue(ComplianceEvidenceResource::getEloquentQuery()->whereKey($auditEvidence->id)->exists());
        $this->assertFalse(ComplianceEvidenceResource::getEloquentQuery()->whereKey($guideline->id)->exists());
    }

    public function test_safety_event_and_finding_workflow_reuses_existing_governance_models(): void
    {
        $leader = $this->leaderUser();
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();

        $event = SepsisSafetyEvent::query()->create([
            'clinical_program_id' => $program->id,
            'occurred_on' => today(),
            'event_type' => 'antibiotic_delay',
            'description' => 'Retraso ficticio de prueba',
            'severity' => 'high',
            'status' => 'open',
        ]);
        $this->assertTrue($leader->can('update', $event));

        $framework = AccreditationFramework::query()->create(['clinical_program_id' => $program->id, 'name' => 'Marco de prueba']);
        $chapter = AccreditationChapter::query()->create(['accreditation_framework_id' => $framework->id, 'code' => 'EXP', 'name' => 'Capítulo de prueba']);
        $standard = AccreditationStandard::query()->create(['accreditation_chapter_id' => $chapter->id, 'code' => 'EXP.01', 'name' => 'Estándar de prueba']);
        $element = MeasurableElement::query()->create([
            'accreditation_standard_id' => $standard->id, 'code' => 'EXP.01.1',
            'name' => 'Elemento de prueba', 'description' => 'Descripción de prueba',
        ]);
        $assessment = ComplianceAssessment::query()->create([
            'measurable_element_id' => $element->id, 'status' => 'non_compliant',
            'assessed_on' => today(),
        ]);
        $finding = AssessmentFinding::query()->create([
            'compliance_assessment_id' => $assessment->id,
            'severity' => 'high',
            'description' => 'Hallazgo ficticio de prueba',
            'status' => 'open',
        ]);
        $action = $finding->correctiveActions()->create([
            'action' => 'Acción ficticia de prueba',
            'responsible_user_id' => $leader->id,
            'status' => 'open',
        ]);

        $this->assertTrue($finding->correctiveActions()->whereKey($action->id)->exists());
        $this->assertSame($leader->id, $action->responsible->id);
    }

    public function test_creating_a_case_without_explicit_status_does_not_crash(): void
    {
        $patient = Patient::query()->create(['identification' => 'TEST-NOSTATUS', 'full_name' => 'Paciente Sin Estado']);

        $case = SepsisCase::query()->create(['patient_id' => $patient->id]);

        $this->assertNotNull($case->id);
    }

    public function test_navigation_labels_still_avoid_jci_wording(): void
    {
        foreach ([
            ProgramResourceResource::getNavigationLabel(),
            ProgramDocumentResource::getNavigationLabel(),
            SafetyEventResource::getNavigationLabel(),
            FindingResource::getNavigationLabel(),
            Tracers::getNavigationLabel(),
        ] as $label) {
            $this->assertDoesNotMatchRegularExpression('/\bjci\b/i', (string) $label);
        }
    }
}
