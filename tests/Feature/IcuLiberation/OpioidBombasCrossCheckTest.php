<?php

namespace Tests\Feature\IcuLiberation;

use App\Models\ClinicalProgram;
use App\Models\DrugInfusionProfile;
use App\Models\IcuStay;
use App\Models\InfusionEvent;
use App\Models\Patient;
use App\Models\PumpDevice;
use App\Services\IcuLiberationIndicatorService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cruza el dato estructurado de bomba (módulo BOMBAS, tabla compartida `icu_stays`) con
 * el componente "A" (Assess/treat pain) del bundle ABCDEF de ICU Liberation: una
 * estancia con opioide en infusión (dato de bomba) debería tener una evaluación de dolor
 * documentada para contar en `opioid_pain_documentation_pct`.
 */
class OpioidBombasCrossCheckTest extends TestCase
{
    use RefreshDatabase;

    private function makeStayWithOpioidInfusion(string $caseNumber, bool $withPainAssessment): IcuStay
    {
        $iculib = ClinicalProgram::query()->where('code', 'ICULIB')->firstOrFail();
        $patient = Patient::query()->create(['identification' => $caseNumber, 'full_name' => "Paciente {$caseNumber}"]);

        $stay = IcuStay::query()->create([
            'clinical_program_id' => $iculib->id,
            'patient_id' => $patient->id,
            'case_number' => $caseNumber,
            'case_sequence' => 1,
            'status' => 'active',
            'admission_at' => now()->startOfYear()->addDays(5),
        ]);

        $device = PumpDevice::query()->create(['hospital_device_id' => 'DEV-'.$caseNumber, 'is_active' => true]);
        $profile = DrugInfusionProfile::query()->where('name', 'Fentanilo')->firstOrFail();

        InfusionEvent::query()->create([
            'pump_device_id' => $device->id,
            'icu_stay_id' => $stay->id,
            'drug_infusion_profile_id' => $profile->id,
            'occurred_at' => CarbonImmutable::now(),
            'medication_name_raw' => 'Fentanilo',
            'attempted_value' => 10,
            'attempted_unit' => 'ml/hr',
            'existing_dose_rate_value' => 10,
            'existing_dose_rate_unit' => 'ml/hr',
            'ingested_at' => now(),
        ]);

        if ($withPainAssessment) {
            $stay->painAssessments()->create(['assessed_at' => now(), 'scale' => 'cpot', 'score' => '2']);
        }

        return $stay;
    }

    public function test_dashboard_reports_full_documentation_when_pain_assessment_exists(): void
    {
        $this->makeStayWithOpioidInfusion('OPIOID-ICUL-1', withPainAssessment: true);

        $metrics = app(IcuLiberationIndicatorService::class)->dashboard(now()->format('Y'));

        $this->assertSame(100.0, $metrics['opioid_pain_documentation_pct']);
    }

    public function test_dashboard_reports_gap_when_pain_assessment_missing(): void
    {
        $this->makeStayWithOpioidInfusion('OPIOID-ICUL-2', withPainAssessment: false);

        $metrics = app(IcuLiberationIndicatorService::class)->dashboard(now()->format('Y'));

        $this->assertSame(0.0, $metrics['opioid_pain_documentation_pct']);
    }

    public function test_dashboard_ignores_stays_without_opioid_infusion(): void
    {
        $iculib = ClinicalProgram::query()->where('code', 'ICULIB')->firstOrFail();
        $patient = Patient::query()->create(['identification' => 'NOOPIOID-1', 'full_name' => 'Paciente sin opioide']);

        IcuStay::query()->create([
            'clinical_program_id' => $iculib->id,
            'patient_id' => $patient->id,
            'case_number' => 'NOOPIOID-1',
            'case_sequence' => 1,
            'status' => 'active',
            'admission_at' => now()->startOfYear()->addDays(5),
        ]);

        $metrics = app(IcuLiberationIndicatorService::class)->dashboard(now()->format('Y'));

        $this->assertNull($metrics['opioid_pain_documentation_pct']);
    }
}
