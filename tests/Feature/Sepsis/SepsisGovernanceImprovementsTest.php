<?php

namespace Tests\Feature\Sepsis;

use App\Enums\CaseStatus;
use App\Enums\ProgramRole;
use App\Filament\Sepsis\Pages\Tracers;
use App\Models\AccreditationChapter;
use App\Models\AccreditationFramework;
use App\Models\AccreditationStandard;
use App\Models\AssessmentFinding;
use App\Models\ClinicalProgram;
use App\Models\ComplianceAssessment;
use App\Models\EvidenceAcknowledgement;
use App\Models\EvidenceDocument;
use App\Models\IndicatorDefinition;
use App\Models\MeasurableElement;
use App\Models\Patient;
use App\Models\ProgramMember;
use App\Models\SepsisBundleTask;
use App\Models\SepsisCase;
use App\Models\SepsisSafetyEvent;
use App\Models\User;
use App\Services\SepsisExecutiveSummaryService;
use App\Services\SepsisIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepsisGovernanceImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private function program(): ClinicalProgram
    {
        return ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
    }

    public function test_updating_an_indicator_definition_formula_archives_the_previous_version(): void
    {
        $program = $this->program();
        $definition = IndicatorDefinition::query()->create([
            'clinical_program_id' => $program->id,
            'code' => 'TEST-01',
            'name' => 'Indicador de prueba',
            'indicator_group' => 'process',
            'numerator' => 'A',
            'denominator' => 'B',
            'target_value' => '50%',
        ]);

        $this->assertSame(1, $definition->version);
        $this->assertSame(0, $definition->versions()->count());

        $definition->update(['numerator' => 'A modificado']);
        $definition->refresh();

        $this->assertSame(2, $definition->version);
        $this->assertSame(1, $definition->versions()->count());
        $this->assertSame('A', $definition->versions()->first()->numerator);
    }

    public function test_case_completeness_excludes_fields_marked_not_applicable(): void
    {
        $patient = Patient::query()->create(['identification' => 'GOV-1', 'full_name' => 'Paciente Completitud']);
        $case = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'status' => CaseStatus::Hospitalized,
            'admission_at' => now(),
            'activation_at' => now(),
        ]);

        $withoutOverride = $case->completenessSummary();
        $this->assertCount(6, $withoutOverride['items']);
        $this->assertLessThan(100.0, $withoutOverride['percentage']);

        $case->update(['field_status' => [
            'antibiotic_at' => 'not_applicable',
            'lactate_at' => 'not_applicable',
            'culture_at' => 'not_applicable',
            'discharged_at' => 'not_applicable',
        ]]);

        $this->assertSame(100.0, $case->fresh()->completenessSummary()['percentage']);
    }

    public function test_evidence_acknowledgement_records_socialization(): void
    {
        $program = $this->program();
        $leader = User::factory()->create(['email' => 'ack-leader@example.test']);
        $member = ProgramMember::query()->create([
            'clinical_program_id' => $program->id, 'user_id' => $leader->id,
            'role' => ProgramRole::Leader, 'is_active' => true,
        ]);
        $document = EvidenceDocument::query()->create([
            'clinical_program_id' => $program->id,
            'title' => 'Protocolo de prueba',
            'institutional_code' => 'PROT-001',
            'evidence_type' => 'protocol',
            'document_reference' => 'ruta/ficticia.pdf',
            'status' => 'draft',
            'socialization_status' => 'pending',
        ]);

        $document->acknowledgements()->create([
            'program_member_id' => $member->id,
            'method' => 'training',
            'acknowledged_on' => today(),
        ]);

        $this->assertSame(1, $document->acknowledgements()->count());
        $this->assertInstanceOf(EvidenceAcknowledgement::class, $document->acknowledgements()->first());
    }

    public function test_safety_event_category_and_finding_link(): void
    {
        $program = $this->program();
        $framework = AccreditationFramework::query()->create(['clinical_program_id' => $program->id, 'name' => 'Marco de prueba']);
        $chapter = AccreditationChapter::query()->create(['accreditation_framework_id' => $framework->id, 'code' => 'GOV', 'name' => 'Capítulo de prueba']);
        $standard = AccreditationStandard::query()->create(['accreditation_chapter_id' => $chapter->id, 'code' => 'GOV.01', 'name' => 'Estándar de prueba']);
        $element = MeasurableElement::query()->create([
            'accreditation_standard_id' => $standard->id, 'code' => 'GOV.01.1',
            'name' => 'Elemento de prueba', 'description' => 'Descripción de prueba',
        ]);
        $assessment = ComplianceAssessment::query()->create([
            'measurable_element_id' => $element->id, 'status' => 'non_compliant', 'assessed_on' => today(),
        ]);
        $finding = AssessmentFinding::query()->create([
            'compliance_assessment_id' => $assessment->id,
            'severity' => 'high',
            'description' => 'Hallazgo de prueba',
            'root_cause' => 'Causa raíz de prueba',
        ]);

        $patient = Patient::query()->create(['identification' => 'GOV-2', 'full_name' => 'Paciente Seguridad']);
        $case = SepsisCase::query()->create(['patient_id' => $patient->id, 'status' => CaseStatus::Hospitalized]);

        $event = SepsisSafetyEvent::query()->create([
            'clinical_program_id' => $program->id,
            'sepsis_case_id' => $case->id,
            'assessment_finding_id' => $finding->id,
            'occurred_on' => today(),
            'event_type' => 'antibiotic_delay',
            'event_category' => 'sentinel_event',
            'description' => 'Prueba',
            'severity' => 'sentinel',
        ]);

        $this->assertSame($finding->id, $event->finding->id);
        $this->assertSame('Causa raíz de prueba', $event->finding->root_cause);
    }

    public function test_readiness_tracking_counts_streak_and_cumulative_cases(): void
    {
        $patient = Patient::query()->create(['identification' => 'GOV-3', 'full_name' => 'Paciente Racha']);

        SepsisCase::query()->create([
            'patient_id' => $patient->id, 'status' => CaseStatus::Completed,
            'activation_at' => now()->subMonthsNoOverflow(2), 'discharged_at' => now()->subMonthsNoOverflow(2),
        ]);
        SepsisCase::query()->create([
            'patient_id' => $patient->id, 'status' => CaseStatus::Completed,
            'activation_at' => now()->subMonthsNoOverflow(1), 'discharged_at' => now()->subMonthsNoOverflow(1),
        ]);

        $summary = app(SepsisExecutiveSummaryService::class)->summary();

        $this->assertSame(2, $summary['readiness']['cumulative_cases']);
        $this->assertSame(2, $summary['readiness']['streak_months']);
        $this->assertFalse($summary['readiness']['meets_case_threshold']);
        $this->assertFalse($summary['readiness']['meets_month_threshold']);
    }

    public function test_tracer_reports_bundle_compliance_missing_records_and_professionals(): void
    {
        $auditor = User::factory()->create(['email' => 'auditor-tracer@example.test', 'name' => 'Auditor Trazador']);
        $patient = Patient::query()->create(['identification' => 'GOV-4', 'full_name' => 'Paciente Trazador']);
        $case = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'status' => CaseStatus::Hospitalized,
            'assigned_auditor_id' => $auditor->id,
            'activation_at' => now()->subHours(2),
        ]);

        SepsisBundleTask::query()->create([
            'sepsis_case_id' => $case->id, 'task_key' => 'antibiotic', 'label' => 'Antibiótico',
            'status' => 'done', 'responsible_user_id' => $auditor->id,
        ]);
        SepsisBundleTask::query()->create([
            'sepsis_case_id' => $case->id, 'task_key' => 'lactate', 'label' => 'Lactato', 'status' => 'done_late',
        ]);
        SepsisBundleTask::query()->create([
            'sepsis_case_id' => $case->id, 'task_key' => 'culture', 'label' => 'Cultivos', 'status' => 'pending',
        ]);

        $page = new Tracers;
        $page->caseId = $case->id;
        $tracer = $page->patientTracer();

        $this->assertSame(3, $tracer['bundle_compliance']['total']);
        $this->assertSame(66.7, $tracer['bundle_compliance']['percentage']);
        $this->assertContains('Auditor Trazador', $tracer['professionals']);
        $this->assertContains('Cultivos', $tracer['missing_records']);
        $this->assertNotContains('Bundle de primera hora', $tracer['missing_records']);
    }

    public function test_summary_readiness_does_not_alter_the_six_core_indicators(): void
    {
        $before = app(SepsisIndicatorService::class)->summary('2026');
        app(SepsisExecutiveSummaryService::class)->summary();
        $after = app(SepsisIndicatorService::class)->summary('2026');

        $this->assertSame($before, $after);
    }
}
