<?php

namespace Tests\Feature\Sepsis;

use App\Models\AccreditationChapter;
use App\Models\AccreditationFramework;
use App\Models\AccreditationStandard;
use App\Models\ClinicalProgram;
use App\Models\CommitteeMeeting;
use App\Models\ComplianceAssessment;
use App\Models\EvidenceDocument;
use App\Models\MeasurableElement;
use App\Models\MeetingAction;
use App\Models\MeetingDecision;
use App\Models\ProgramCommittee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProgramFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sepsis_program_is_created_without_personal_names(): void
    {
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();

        $this->assertSame('Código Sepsis', $program->short_name);
        $this->assertSame('implementation', $program->status);
        $this->assertNull($program->medical_leader);
        $this->assertNull($program->nursing_leader);
        $this->assertNull($program->quality_leader);
    }

    public function test_governance_records_committee_meeting_decision_and_action(): void
    {
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        $committee = ProgramCommittee::query()->create([
            'clinical_program_id' => $program->id,
            'name' => 'Comité ficticio de prueba',
        ]);
        $meeting = CommitteeMeeting::query()->create([
            'program_committee_id' => $committee->id,
            'title' => 'Reunión ficticia',
            'scheduled_at' => '2026-07-24 09:00:00',
        ]);
        $decision = MeetingDecision::query()->create([
            'committee_meeting_id' => $meeting->id,
            'decision' => 'Decisión ficticia para validación',
        ]);
        $action = MeetingAction::query()->create([
            'committee_meeting_id' => $meeting->id,
            'meeting_decision_id' => $decision->id,
            'action' => 'Compromiso ficticio',
            'due_on' => '2026-08-01',
        ]);

        $this->assertTrue($committee->meetings()->whereKey($meeting)->exists());
        $this->assertTrue($meeting->decisions()->whereKey($decision)->exists());
        $this->assertSame('open', $action->status);
    }

    public function test_standard_element_assessment_and_evidence_are_related(): void
    {
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        $framework = AccreditationFramework::query()->create([
            'clinical_program_id' => $program->id,
            'name' => 'Marco normativo ficticio',
        ]);
        $chapter = AccreditationChapter::query()->create([
            'accreditation_framework_id' => $framework->id,
            'code' => 'CAP-TEST',
            'name' => 'Capítulo de prueba',
        ]);
        $standard = AccreditationStandard::query()->create([
            'accreditation_chapter_id' => $chapter->id,
            'code' => 'EST-TEST',
            'name' => 'Estándar de prueba',
        ]);
        $element = MeasurableElement::query()->create([
            'accreditation_standard_id' => $standard->id,
            'code' => 'EL-TEST',
            'name' => 'Elemento ficticio',
            'description' => 'Descripción ficticia',
        ]);
        $assessment = ComplianceAssessment::query()->create([
            'measurable_element_id' => $element->id,
            'status' => 'partially_compliant',
            'progress_percentage' => 50,
            'assessed_on' => '2026-07-24',
        ]);
        $evidence = EvidenceDocument::query()->create([
            'clinical_program_id' => $program->id,
            'title' => 'Evidencia ficticia',
            'evidence_type' => 'report',
            'status' => 'verified',
            'document_reference' => 'testing/evidence/ficticia.pdf',
            'expires_on' => '2026-07-23',
        ]);
        $evidence->elements()->attach($element);

        $this->assertSame(50, $assessment->progress_percentage);
        $this->assertTrue($evidence->elements()->whereKey($element)->exists());
        $this->assertTrue($evidence->isExpired());
    }

    public function test_foundation_migration_tables_exist(): void
    {
        foreach ([
            'clinical_programs', 'program_members', 'clinical_audits',
            'program_committees', 'committee_meetings', 'meeting_decisions',
            'meeting_actions', 'accreditation_frameworks', 'accreditation_chapters',
            'accreditation_standards', 'measurable_elements',
            'compliance_assessments', 'evidence_documents',
            'assessment_findings', 'corrective_actions',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Falta la tabla {$table}.");
        }
    }

    /**
     * Revierte TODAS las migraciones (Laravel resuelve el orden real por lotes, evitando
     * listas manuales frágiles a medida que crece el esquema) y las vuelve a aplicar.
     * Verifica que ningún down() esté roto, incluidas las tablas fundacionales del programa.
     */
    public function test_foundation_migrations_are_reversible(): void
    {
        Artisan::call('migrate:rollback', ['--step' => 100, '--force' => true]);

        $this->assertFalse(Schema::hasTable('clinical_programs'));
        $this->assertFalse(Schema::hasTable('clinical_audits'));
        $this->assertFalse(Schema::hasTable('program_committees'));
        $this->assertFalse(Schema::hasTable('accreditation_frameworks'));
        $this->assertFalse(Schema::hasTable('users'));

        Artisan::call('migrate', ['--force' => true]);

        $this->assertTrue(Schema::hasTable('clinical_programs'));
        $this->assertTrue(Schema::hasTable('clinical_audits'));
        $this->assertTrue(Schema::hasTable('program_committees'));
        $this->assertTrue(Schema::hasTable('accreditation_frameworks'));
        $this->assertTrue(Schema::hasTable('users'));
    }
}
