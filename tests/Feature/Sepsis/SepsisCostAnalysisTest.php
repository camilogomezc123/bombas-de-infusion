<?php

namespace Tests\Feature\Sepsis;

use App\Enums\CaseStatus;
use App\Models\Patient;
use App\Models\SepsisCase;
use App\Services\SepsisCostService;
use App\Services\SepsisIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepsisCostAnalysisTest extends TestCase
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

    public function test_cost_summary_computes_totals_min_max_and_mortality_breakdown(): void
    {
        $patient = Patient::query()->create(['identification' => 'COST-1', 'full_name' => 'Paciente Costo']);

        $this->makeCase($patient, ['total_cost' => 1000, 'deceased' => false, 'er_stay_days' => 1, 'clinic_stay_days' => 1]);
        $this->makeCase($patient, ['total_cost' => 5000, 'deceased' => true, 'clinic_stay_days' => 4]);
        $this->makeCase($patient, ['total_cost' => null]); // sin costo, no debe contar

        $summary = app(SepsisCostService::class)->summary('2026');

        $this->assertSame(2, $summary['count']);
        $this->assertSame(6000.0, $summary['total_cost']);
        $this->assertSame(1000.0, $summary['min_cost']);
        $this->assertSame(5000.0, $summary['max_cost']);
        $this->assertSame(3000.0, $summary['avg_cost']);
        $this->assertSame(5000.0, $summary['avg_cost_deceased']);
        $this->assertSame(1000.0, $summary['avg_cost_alive']);
        $this->assertSame(1, $summary['count_deceased']);
        $this->assertSame(1, $summary['count_alive']);
        $this->assertCount(2, $summary['top_cases']);
        $this->assertSame(5000.0, (float) $summary['top_cases'][0]->total_cost);
    }

    public function test_cost_summary_returns_empty_state_without_crashing(): void
    {
        $summary = app(SepsisCostService::class)->summary('2019');

        $this->assertSame(0, $summary['count']);
        $this->assertNull($summary['total_cost']);
        $this->assertNull($summary['avg_cost_per_stay_day']);
    }

    public function test_adding_cost_data_does_not_change_the_bundle_formula_for_the_same_case(): void
    {
        $patient = Patient::query()->create(['identification' => 'COST-2', 'full_name' => 'Paciente Costo Indicadores']);

        $case = $this->makeCase($patient, [
            'activation_at' => '2026-07-01 10:00',
            'antibiotic_at' => '2026-07-01 10:30',
            'lactate_at' => '2026-07-01 10:20',
            'culture_at' => '2026-07-01 10:10',
            'septic_shock' => false,
            'deceased' => false,
        ]);

        $before = app(SepsisIndicatorService::class)->dashboard('2026', '2026/07')['current']['bundle_pct'];

        // Registrar un costo en el mismo caso no debe alterar en absoluto la fórmula del bundle.
        $case->update(['total_cost' => 123456]);

        $after = app(SepsisIndicatorService::class)->dashboard('2026', '2026/07')['current']['bundle_pct'];

        $this->assertSame(100.0, $before);
        $this->assertSame($before, $after);
        $this->assertArrayNotHasKey('total_cost', app(SepsisIndicatorService::class)->summary('2026'));
    }
}
