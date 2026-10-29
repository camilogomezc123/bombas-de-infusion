<?php

namespace Tests\Feature\Pics;

use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PicsRiskScoreTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(array $attributes, string $sex = 'M'): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('R-'), 'full_name' => 'Riesgo', 'sex' => $sex]);

        return PicsCase::query()->create(array_merge([
            'clinical_program_id' => $program->id,
            'patient_id' => $patient->id,
        ], $attributes));
    }

    public function test_case_with_no_risk_factors_is_low_risk(): void
    {
        $case = $this->makeCase([]);

        $result = $case->calculateRisk();

        $this->assertSame(0, $result['score']);
        $this->assertSame('bajo', $result['level']);
        $this->assertSame([], $result['factors']);
    }

    public function test_score_greater_than_two_is_high_risk(): void
    {
        $case = $this->makeCase(['age_at_admission' => 70, 'shock_or_sepsis' => true]); // 2 + 2 = 4

        $result = $case->calculateRisk();

        $this->assertSame(4, $result['score']);
        $this->assertSame('alto', $result['level']);
    }

    public function test_score_of_two_is_medium_risk_not_high(): void
    {
        $case = $this->makeCase(['age_at_admission' => 70, 'icu_los_days' => 6]); // 2 + 0? 6 days is >5 -> +1... let's use two independent +1 factors

        // icu_los_days=6 (>5, not >=7) -> +1; barthel<100 -> +1. Total 2.
        $case->update(['icu_los_days' => 6, 'age_at_admission' => null, 'barthel_at_discharge' => 90]);

        $result = $case->calculateRisk();

        $this->assertSame(2, $result['score']);
        $this->assertSame('medio', $result['level']);
    }

    public function test_icu_los_tiers_match_exact_day_bands(): void
    {
        $this->assertSame(1, $this->makeCase(['icu_los_days' => 6])->calculateRisk()['score']);
        $this->assertSame(1, $this->makeCase(['icu_los_days' => 7])->calculateRisk()['score']);
        $this->assertSame(1, $this->makeCase(['icu_los_days' => 13])->calculateRisk()['score']);
        $this->assertSame(2, $this->makeCase(['icu_los_days' => 14])->calculateRisk()['score']);
        $this->assertSame(0, $this->makeCase(['icu_los_days' => 5])->calculateRisk()['score']);
    }

    public function test_dauci_uses_sex_specific_handgrip_threshold(): void
    {
        $female = $this->makeCase(['handgrip_kg' => 15], sex: 'F'); // < 16 -> DAUCI
        $this->assertSame(2, $female->calculateRisk()['score']);

        $maleSameValue = $this->makeCase(['handgrip_kg' => 15], sex: 'M'); // < 27 -> also DAUCI for a man
        $this->assertSame(2, $maleSameValue->calculateRisk()['score']);

        $maleNormal = $this->makeCase(['handgrip_kg' => 30], sex: 'M'); // >= 27 -> not DAUCI
        $this->assertSame(0, $maleNormal->calculateRisk()['score']);
    }

    public function test_mrc_and_handgrip_do_not_double_count_dauci(): void
    {
        $case = $this->makeCase(['mrc_total' => 40, 'handgrip_kg' => 10], sex: 'F');

        $this->assertSame(2, $case->calculateRisk()['score']);
    }

    public function test_recalculate_risk_persists_score_level_and_factors(): void
    {
        $case = $this->makeCase(['age_at_admission' => 65, 'shock_or_sepsis' => true]);

        $case->recalculateRisk()->save();
        $case->refresh();

        $this->assertSame(4, $case->risk_score);
        $this->assertSame('alto', $case->risk_level);
        $this->assertCount(2, $case->risk_factors);
    }
}
