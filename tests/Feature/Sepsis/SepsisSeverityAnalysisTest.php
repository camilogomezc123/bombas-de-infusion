<?php

namespace Tests\Feature\Sepsis;

use App\Enums\CaseStatus;
use App\Models\Patient;
use App\Models\SepsisCase;
use App\Services\SepsisSeverityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepsisSeverityAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(Patient $patient, array $overrides = []): SepsisCase
    {
        return SepsisCase::query()->create(array_merge([
            'patient_id' => $patient->id,
            'status' => CaseStatus::Completed,
            'month' => '2026/07',
            'is_valid' => true,
            'is_cancelled' => false,
        ], $overrides));
    }

    public function test_severity_by_case_uses_the_maximum_score_across_screenings(): void
    {
        $patient = Patient::query()->create(['identification' => 'SEV-1', 'full_name' => 'Paciente Severidad']);
        $case = $this->makeCase($patient);
        $case->screenings()->create(['occurred_at' => now()->subHours(2), 'news2_score' => 3, 'sofa_score' => 2]);
        $case->screenings()->create(['occurred_at' => now(), 'news2_score' => 8, 'sofa_score' => 5]);

        $severity = app(SepsisSeverityService::class)->severityByCase('2026', null);

        $this->assertSame(8, $severity[$case->id]['news2']);
        $this->assertSame(5, $severity[$case->id]['sofa']);
    }

    public function test_monthly_trend_averages_max_scores_and_ignores_cases_without_screenings(): void
    {
        $patient = Patient::query()->create(['identification' => 'SEV-2', 'full_name' => 'Paciente Severidad 2']);
        $withScreening = $this->makeCase($patient);
        $withScreening->screenings()->create(['occurred_at' => now(), 'news2_score' => 4, 'sofa_score' => 6]);
        $anotherWithScreening = $this->makeCase($patient);
        $anotherWithScreening->screenings()->create(['occurred_at' => now(), 'news2_score' => 6, 'sofa_score' => 10]);
        $this->makeCase($patient); // sin tamizaje, no debe distorsionar el promedio

        $trend = app(SepsisSeverityService::class)->monthlyTrend('2026');

        $this->assertCount(1, $trend);
        $this->assertSame('2026/07', $trend[0]['month']);
        $this->assertSame(5.0, $trend[0]['avg_news2']);
        $this->assertSame(8.0, $trend[0]['avg_sofa']);
    }

    public function test_mortality_cross_tab_computes_rate_within_severity_and_cost_bands(): void
    {
        $patient = Patient::query()->create(['identification' => 'SEV-3', 'full_name' => 'Paciente Severidad 3']);

        $low = $this->makeCase($patient, ['deceased' => false, 'total_cost' => 1000]);
        $low->screenings()->create(['occurred_at' => now(), 'news2_score' => 2, 'sofa_score' => 1]);

        $highDead = $this->makeCase($patient, ['deceased' => true, 'total_cost' => 9000]);
        $highDead->screenings()->create(['occurred_at' => now(), 'news2_score' => 9, 'sofa_score' => 12]);

        $highAlive = $this->makeCase($patient, ['deceased' => false, 'total_cost' => 8500]);
        $highAlive->screenings()->create(['occurred_at' => now(), 'news2_score' => 8, 'sofa_score' => 11]);

        $crossTab = app(SepsisSeverityService::class)->mortalityCrossTab('2026', null);

        $this->assertSame(3, $crossTab['count']);
        $highNewsRow = collect($crossTab['news2'])->firstWhere('label', 'Alto (≥7)');
        $totalInHighBand = collect($highNewsRow['cells'])->sum('n');
        $this->assertSame(2, $totalInHighBand);
    }

    public function test_cost_by_severity_band_reports_average_cost_per_band(): void
    {
        $patient = Patient::query()->create(['identification' => 'SEV-4', 'full_name' => 'Paciente Severidad 4']);

        $low = $this->makeCase($patient, ['total_cost' => 1000]);
        $low->screenings()->create(['occurred_at' => now(), 'news2_score' => 1]);

        $high = $this->makeCase($patient, ['total_cost' => 5000]);
        $high->screenings()->create(['occurred_at' => now(), 'news2_score' => 10]);

        $result = app(SepsisSeverityService::class)->costBySeverityBand('2026', null);
        $lowRow = collect($result['news2'])->firstWhere('label', 'Bajo (0-4)');
        $highRow = collect($result['news2'])->firstWhere('label', 'Alto (≥7)');

        $this->assertSame(1000.0, $lowRow['avg_cost']);
        $this->assertSame(5000.0, $highRow['avg_cost']);
    }
}
