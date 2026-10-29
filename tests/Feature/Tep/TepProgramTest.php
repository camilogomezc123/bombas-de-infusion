<?php

namespace Tests\Feature\Tep;

use App\Models\ClinicalProgram;
use App\Models\IndicatorDefinition;
use App\Models\Patient;
use App\Models\TepCase;
use App\Services\TepIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TepProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_and_shared_indicator_definitions_are_installed(): void
    {
        $program = ClinicalProgram::query()->where('code', 'TEP')->firstOrFail();

        $this->assertSame('Código TEP', $program->short_name);
        $this->assertSame(8, IndicatorDefinition::query()->where('clinical_program_id', $program->id)->count());
    }

    public function test_category_is_never_inferred_and_dashboard_uses_documented_data(): void
    {
        $program = ClinicalProgram::query()->where('code', 'TEP')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'TEP-TEST-1', 'full_name' => 'Caso prueba']);
        $case = TepCase::query()->create([
            'clinical_program_id' => $program->id,
            'patient_id' => $patient->id,
            'case_number' => 'TEP-TEST-0001',
            'case_sequence' => 1,
            'admission_at' => '2026-07-01 08:00:00',
            'diagnosis_at' => '2026-07-01 09:00:00',
            'tep_confirmed' => true,
            'shock' => true,
            'pretest_method' => 'wells',
            'pretest_result' => 'high',
            'anticoagulation_started_at' => '2026-07-01 09:20:00',
            'is_valid' => true,
            'is_cancelled' => false,
        ]);

        $this->assertNull($case->fresh()->aha_category);
        $summary = app(TepIndicatorService::class)->dashboard('2026');
        $this->assertSame(100.0, $summary['pretest_documented_pct']);
        $this->assertSame(0.0, $summary['category_documented_pct']);
        $this->assertSame(20.0, $summary['median_diagnosis_anticoagulation']);
    }
}
