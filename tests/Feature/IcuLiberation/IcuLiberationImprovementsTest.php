<?php

namespace Tests\Feature\IcuLiberation;

use App\Models\AccreditationChapter;
use App\Models\AccreditationFramework;
use App\Models\AccreditationStandard;
use App\Models\AssessmentFinding;
use App\Models\ClinicalProgram;
use App\Models\ComplianceAssessment;
use App\Models\IcuStay;
use App\Models\IcuStayAudit;
use App\Models\IndicatorDefinition;
use App\Models\MeasurableElement;
use App\Models\Patient;
use App\Services\IcuLiberationIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IcuLiberationImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private function program(): ClinicalProgram
    {
        return ClinicalProgram::query()->where('code', 'ICULIB')->firstOrFail();
    }

    public function test_live_indicators_are_seeded_with_exclusion_criteria_and_validation_method(): void
    {
        $definition = IndicatorDefinition::query()
            ->where('clinical_program_id', $this->program()->id)
            ->where('code', 'COMP-01')
            ->firstOrFail();

        $this->assertNotNull($definition->exclusion_criteria);
        $this->assertNotNull($definition->validation_method);
    }

    public function test_component_marked_not_applicable_is_excluded_from_bundle_denominator(): void
    {
        $program = $this->program();
        $patient = Patient::query()->create(['identification' => 'ICUL-IMP-1', 'full_name' => 'Paciente Mejora 1']);

        $stay = IcuStay::query()->create([
            'clinical_program_id' => $program->id, 'patient_id' => $patient->id,
            'case_number' => 'UCI-IMP-1', 'case_sequence' => 201, 'status' => 'active',
            'admission_at' => now()->subDays(3), 'mechanical_ventilation' => false,
            'is_valid' => true, 'is_cancelled' => false,
            'field_status' => ['mobility' => 'not_applicable'],
        ]);

        $stay->painAssessments()->create(['assessed_at' => now(), 'scale' => 'nrs', 'score' => '2']);
        $stay->sedationAssessments()->create(['assessed_at' => now(), 'goal_value' => '-1', 'actual_value' => '-1']);
        $stay->deliriumAssessments()->create(['assessed_at' => now(), 'result' => 'negative']);
        // Sin registro de movilidad — pero está marcada "no aplica", no debe contar como incumplimiento.

        $dashboard = app(IcuLiberationIndicatorService::class)->dashboard();

        // 3 componentes aplicables (pain, sedation, delirium), los 3 cumplidos => 100%.
        $this->assertSame(100.0, $dashboard['bundle_compliance_pct']);
    }

    public function test_bundle_status_for_date_reflects_overrides_and_ventilation(): void
    {
        $program = $this->program();
        $patient = Patient::query()->create(['identification' => 'ICUL-IMP-2', 'full_name' => 'Paciente Mejora 2']);

        $stay = IcuStay::query()->create([
            'clinical_program_id' => $program->id, 'patient_id' => $patient->id,
            'case_number' => 'UCI-IMP-2', 'case_sequence' => 202, 'status' => 'active',
            'admission_at' => now(), 'mechanical_ventilation' => false,
            'field_status' => ['delirium' => 'not_performed'],
        ]);

        $status = $stay->bundleStatusForDate(today());

        $this->assertArrayNotHasKey('sat', $status);
        $this->assertArrayNotHasKey('sbt', $status);
        $this->assertTrue($status['delirium']['applicable']);
        $this->assertFalse($status['delirium']['done']);
        $this->assertSame('not_performed', $status['delirium']['override']);
    }

    public function test_icu_stay_audit_can_link_to_a_formal_finding(): void
    {
        $program = $this->program();
        $framework = AccreditationFramework::query()->create(['clinical_program_id' => $program->id, 'name' => 'Marco de prueba ICU']);
        $chapter = AccreditationChapter::query()->create(['accreditation_framework_id' => $framework->id, 'code' => 'ICUL', 'name' => 'Capítulo de prueba']);
        $standard = AccreditationStandard::query()->create(['accreditation_chapter_id' => $chapter->id, 'code' => 'ICUL.01', 'name' => 'Estándar de prueba']);
        $element = MeasurableElement::query()->create([
            'accreditation_standard_id' => $standard->id, 'code' => 'ICUL.01.1',
            'name' => 'Elemento de prueba', 'description' => 'Descripción de prueba',
        ]);
        $assessment = ComplianceAssessment::query()->create([
            'measurable_element_id' => $element->id, 'status' => 'non_compliant', 'assessed_on' => today(),
        ]);
        $finding = AssessmentFinding::query()->create([
            'compliance_assessment_id' => $assessment->id, 'severity' => 'high',
            'description' => 'Hallazgo de prueba', 'root_cause' => 'Causa raíz de prueba',
        ]);

        $patient = Patient::query()->create(['identification' => 'ICUL-IMP-3', 'full_name' => 'Paciente Mejora 3']);
        $stay = IcuStay::query()->create([
            'clinical_program_id' => $program->id, 'patient_id' => $patient->id,
            'case_number' => 'UCI-IMP-3', 'case_sequence' => 203, 'status' => 'active', 'admission_at' => now(),
        ]);

        $audit = IcuStayAudit::query()->create([
            'icu_stay_id' => $stay->id, 'status' => 'in_progress',
            'requires_phva' => true, 'assessment_finding_id' => $finding->id,
        ]);

        $this->assertSame($finding->id, $audit->finding->id);
        $this->assertSame('Causa raíz de prueba', $audit->finding->root_cause);
    }

    public function test_overdue_pics_followups_counts_discharged_stays_past_checkpoint_without_contact(): void
    {
        $program = $this->program();

        $overduePatient = Patient::query()->create(['identification' => 'ICUL-IMP-4', 'full_name' => 'Paciente Vencido']);
        $overdue = IcuStay::query()->create([
            'clinical_program_id' => $program->id, 'patient_id' => $overduePatient->id,
            'case_number' => 'UCI-IMP-4', 'case_sequence' => 204, 'status' => 'discharged_hospital',
            'admission_at' => now()->subDays(40), 'hospital_discharge_at' => now()->subDays(35),
            'is_valid' => true, 'is_cancelled' => false,
        ]);
        // 30d venció (egreso hace 35 días) y no hay seguimiento con contacto logrado.

        $onTimePatient = Patient::query()->create(['identification' => 'ICUL-IMP-5', 'full_name' => 'Paciente al Día']);
        $onTime = IcuStay::query()->create([
            'clinical_program_id' => $program->id, 'patient_id' => $onTimePatient->id,
            'case_number' => 'UCI-IMP-5', 'case_sequence' => 205, 'status' => 'discharged_hospital',
            'admission_at' => now()->subDays(40), 'hospital_discharge_at' => now()->subDays(35),
            'is_valid' => true, 'is_cancelled' => false,
        ]);
        foreach (['48_72h', '7d', '30d'] as $checkpoint) {
            $onTime->picsFollowups()->create(['checkpoint' => $checkpoint, 'contact_achieved' => true]);
        }

        $count = app(IcuLiberationIndicatorService::class)->overduePicsFollowups();

        $this->assertSame(1, $count);
    }
}
