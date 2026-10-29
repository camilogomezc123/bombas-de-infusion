<?php

namespace Tests\Feature\Infarto;

use App\Enums\UserRole;
use App\Models\AcsCase;
use App\Models\ClinicalAudit;
use App\Models\ClinicalProgram;
use App\Models\ClinicalRule;
use App\Models\Patient;
use App\Models\User;
use App\Services\AcsDataQualityService;
use App\Services\AcsIndicatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcsProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_infarto_program_is_installed_without_duplicating_clinical_program_structure(): void
    {
        $this->assertDatabaseHas('clinical_programs', ['code' => 'INFARTO', 'is_active' => true]);
        $this->assertSame(1, ClinicalProgram::query()->where('code', 'INFARTO')->count());
    }

    public function test_ecg_within_ten_minutes_is_calculated_from_first_medical_contact(): void
    {
        $this->case(['first_medical_contact_at' => '2026-07-01 08:00:00', 'ecg_interpreted_at' => '2026-07-01 08:10:00']);

        $metrics = app(AcsIndicatorService::class)->dashboard('2026');

        $this->assertSame(100.0, $metrics['ecg_10_pct']);
    }

    public function test_direct_and_transferred_stemi_use_different_fmc_device_targets(): void
    {
        $this->case(['acs_type' => 'stemi', 'entry_route' => 'direct', 'first_medical_contact_at' => '2026-07-01 08:00:00', 'first_device_at' => '2026-07-01 09:30:00']);
        $this->case(['acs_type' => 'stemi', 'entry_route' => 'transfer', 'first_medical_contact_at' => '2026-07-02 08:00:00', 'first_device_at' => '2026-07-02 10:00:00']);

        $metrics = app(AcsIndicatorService::class)->dashboard('2026');

        $this->assertSame(100.0, $metrics['fmc_device_direct_90_pct']);
        $this->assertSame(100.0, $metrics['fmc_device_transfer_120_pct']);
    }

    public function test_nste_early_angiography_and_hospital_mortality_are_auditable(): void
    {
        $this->case(['acs_type' => 'nstemi', 'nste_strategy' => 'early', 'diagnosis_at' => '2026-07-01 08:00:00', 'angiography_at' => '2026-07-02 07:59:00']);
        $this->case(['acs_type' => 'stemi', 'death_at' => '2026-07-03 08:00:00']);

        $metrics = app(AcsIndicatorService::class)->dashboard('2026');

        $this->assertSame(100.0, $metrics['nste_angio_24_pct']);
        $this->assertSame(50.0, $metrics['hospital_mortality_pct']);
    }

    public function test_administrator_can_open_infarto_dashboard_and_case_record(): void
    {
        $user = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $case = $this->case(['acs_type' => 'stemi']);

        $this->actingAs($user)->get('/admin')->assertOk()
            ->assertSee('Infarto')
            ->assertSee('/infarto', false);
        $this->actingAs($user)->get('/infarto')->assertOk()->assertSee('Centro de Excelencia de Infarto');
        $this->actingAs($user)->get("/infarto/acs-cases/{$case->id}")->assertOk();
    }

    public function test_grace_documentation_is_crossed_with_mortality_and_stays(): void
    {
        $this->case([
            'acs_type' => 'nstemi', 'grace_score' => 155, 'grace_risk_category' => 'high',
            'grace_assessed_at' => '2026-07-01 09:00:00', 'hospital_stay_days' => 8,
            'icu_stay_days' => 4, 'death_at' => '2026-07-03 08:00:00',
        ]);
        $this->case([
            'acs_type' => 'nstemi', 'grace_score' => 149, 'grace_risk_category' => 'high',
            'grace_assessed_at' => '2026-07-02 09:00:00', 'hospital_stay_days' => 6,
            'icu_stay_days' => 2,
        ]);
        $this->case(['acs_type' => 'nstemi']);

        $metrics = app(AcsIndicatorService::class)->dashboard('2026');
        $highRisk = $metrics['grace_analysis']['high'];

        $this->assertSame(66.7, $metrics['grace_documented_pct']);
        $this->assertSame(2, $highRisk['cases']);
        $this->assertSame(50.0, $highRisk['mortality_pct']);
        $this->assertSame(7.0, $highRisk['median_hospital_stay']);
        $this->assertSame(3.0, $highRisk['median_icu_stay']);
    }

    public function test_clinical_rules_are_versioned_per_program(): void
    {
        $program = ClinicalProgram::query()->where('code', 'INFARTO')->firstOrFail();

        $this->assertDatabaseHas('clinical_rules', [
            'clinical_program_id' => $program->id,
            'code' => 'STEMI-FMC-90',
            'version' => '1.0',
            'status' => 'draft',
        ]);
        $this->assertSame(5, ClinicalRule::query()->where('clinical_program_id', $program->id)->count());
    }

    public function test_sca_changes_use_the_shared_clinical_audit(): void
    {
        $case = $this->case(['acs_type' => 'nstemi']);
        $case->update(['grace_score' => 145]);

        $this->assertDatabaseHas('clinical_audits', [
            'clinical_program_id' => $case->clinical_program_id,
            'auditable_type' => $case->getMorphClass(),
            'auditable_id' => $case->id,
            'event' => 'updated',
            'field' => 'grace_score',
            'new_value' => '145',
        ]);
        $this->assertGreaterThan(0, ClinicalAudit::query()->where('auditable_id', $case->id)->count());
    }

    public function test_data_quality_explains_missing_fields_and_chronology(): void
    {
        $case = $this->case([
            'acs_type' => 'stemi',
            'symptom_onset_at' => '2026-07-01 09:00:00',
            'first_medical_contact_at' => '2026-07-01 08:00:00',
            'ecg_interpreted_at' => null,
            'reperfusion_strategy' => 'none',
        ]);

        $assessment = app(AcsDataQualityService::class)->assess($case);

        $this->assertContains('ECG interpretado', $assessment['missing']);
        $this->assertContains('Justificación de no reperfusión', $assessment['missing']);
        $this->assertContains('El FMC ocurre antes del inicio de síntomas', $assessment['issues']);
        $this->assertLessThan(100, $assessment['percentage']);
    }

    private function case(array $attributes): AcsCase
    {
        $patient = Patient::query()->create([
            'identification' => 'TEST-'.fake()->unique()->numerify('#####'),
            'full_name' => 'Paciente de prueba',
        ]);
        $sequence = (int) AcsCase::query()->max('case_sequence') + 1;
        $admission = Carbon::parse($attributes['admission_at'] ?? $attributes['first_medical_contact_at'] ?? $attributes['diagnosis_at'] ?? '2026-07-01 08:00:00');

        return AcsCase::query()->create(array_merge([
            'clinical_program_id' => ClinicalProgram::query()->where('code', 'INFARTO')->value('id'),
            'patient_id' => $patient->id,
            'case_number' => 'IAM-TEST-'.$sequence,
            'case_sequence' => $sequence,
            'status' => 'in_progress',
            'acs_type' => 'nstemi',
            'admission_at' => $admission,
            'is_valid' => true,
            'is_cancelled' => false,
        ], $attributes));
    }
}
