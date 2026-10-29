<?php

namespace Tests\Feature;

use App\Enums\CaseStatus;
use App\Models\Patient;
use App\Models\SepsisCase;
use App\Services\SepsisIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepsisIndicatorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_the_six_main_indicators(): void
    {
        $patient = Patient::query()->create([
            'identification' => 'SEPSIS-IND-1',
            'full_name' => 'Paciente Indicadores',
        ]);

        // Caso A: sepsis sin choque, bundle completo, fallece a los 10 días.
        $this->createCase($patient, 1, [
            'activation_at' => '2026-06-01 10:00',
            'antibiotic_at' => '2026-06-01 10:30',
            'lactate_at' => '2026-06-01 10:45',
            'culture_at' => '2026-06-01 10:20',
            'septic_shock' => false,
            'deceased' => true,
            'death_at' => '2026-06-11 08:00',
        ]);

        // Caso B: choque séptico, antibiótico tardío (bundle no), alcanza meta de PAM, vive.
        $this->createCase($patient, 2, [
            'activation_at' => '2026-06-05 10:00',
            'antibiotic_at' => '2026-06-05 11:30',
            'septic_shock' => true,
            'map_goal_met' => true,
            'deceased' => false,
        ]);

        // Caso C: choque séptico, AB a tiempo pero sin lactato (bundle no),
        // no alcanza PAM y fallece a los 40 días (hospitalaria sí, a 30 días no).
        $this->createCase($patient, 3, [
            'activation_at' => '2026-06-10 10:00',
            'antibiotic_at' => '2026-06-10 10:50',
            'septic_shock' => true,
            'map_goal_met' => false,
            'deceased' => true,
            'death_at' => '2026-07-20 08:00',
        ]);

        $summary = app(SepsisIndicatorService::class)->summary('2026', '2026/06');

        $this->assertSame(3, $summary['total']);
        $this->assertSame(3, $summary['activated_total']);
        $this->assertSame(1, $summary['sepsis_total']);
        $this->assertSame(2, $summary['shock_total']);

        // Desglose del bundle de la primera hora.
        $this->assertSame(66.7, $summary['bundle_ab_pct']);
        $this->assertSame(33.3, $summary['bundle_lactate_pct']);
        $this->assertSame(33.3, $summary['bundle_culture_pct']);
        $this->assertSame(33.3, $summary['bundle_pct']);

        // Indicador 2: meta de PAM en choque séptico.
        $this->assertSame(50.0, $summary['map_goal_pct']);

        // Indicadores 3 y 4: mortalidad hospitalaria.
        $this->assertSame(100.0, $summary['mort_hosp_sepsis_pct']);
        $this->assertSame(50.0, $summary['mort_hosp_shock_pct']);

        // Indicadores 5 y 6: mortalidad a 30 días.
        $this->assertSame(100.0, $summary['mort_30d_sepsis_pct']);
        $this->assertSame(0.0, $summary['mort_30d_shock_pct']);

        // Cumplimiento de metas.
        $this->assertFalse(SepsisIndicatorService::meetsGoal('bundle_pct', $summary['bundle_pct']));
        $this->assertFalse(SepsisIndicatorService::meetsGoal('map_goal_pct', $summary['map_goal_pct']));
        $this->assertFalse(SepsisIndicatorService::meetsGoal('mort_hosp_sepsis_pct', $summary['mort_hosp_sepsis_pct']));
        $this->assertTrue(SepsisIndicatorService::meetsGoal('mort_hosp_shock_pct', $summary['mort_hosp_shock_pct']));
        $this->assertFalse(SepsisIndicatorService::meetsGoal('mort_30d_sepsis_pct', $summary['mort_30d_sepsis_pct']));
        $this->assertTrue(SepsisIndicatorService::meetsGoal('mort_30d_shock_pct', $summary['mort_30d_shock_pct']));
        $this->assertNull(SepsisIndicatorService::meetsGoal('bundle_pct', null));
    }

    public function test_month_is_derived_from_the_discharge_date(): void
    {
        $patient = Patient::query()->create([
            'identification' => 'SEPSIS-MES-1',
            'full_name' => 'Paciente Mes',
        ]);

        $case = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'case_number' => 'SEP-MES-1',
            'case_sequence' => 99,
            'status' => CaseStatus::Completed,
            'month' => '1999/01',
            'is_valid' => true,
            'is_cancelled' => false,
            'discharged_at' => '2026-07-10 14:00',
        ]);

        $this->assertSame('2026/07', $case->month);

        $case->discharged_at = '2026-08-02 09:00';
        $case->save();

        $this->assertSame('2026/08', $case->fresh()->month);
    }

    public function test_open_case_uses_available_date_and_feeds_partial_indicators(): void
    {
        $patient = Patient::query()->create([
            'identification' => 'SEPSIS-PROCESO-1',
            'full_name' => 'Paciente en Proceso',
        ]);

        $case = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'case_number' => 'SEP-PROCESO-1',
            'case_sequence' => 100,
            'status' => CaseStatus::Hospitalized,
            'registered_on' => '2026-05-03',
            'activation_at' => '2026-05-03 09:00',
            'antibiotic_at' => '2026-05-03 09:40',
            'is_valid' => true,
            'is_cancelled' => false,
        ]);

        $this->assertSame('2026/05', $case->month);
        $this->assertContains($case->status, CaseStatus::inProcess());

        $summary = app(SepsisIndicatorService::class)->summary('2026', '2026/05');

        $this->assertSame(1, $summary['total']);
        $this->assertSame(1, $summary['activated_total']);
        $this->assertSame(100.0, $summary['bundle_ab_pct']);
    }

    private function createCase(Patient $patient, int $sequence, array $attributes): void
    {
        SepsisCase::query()->create(array_merge([
            'patient_id' => $patient->id,
            'case_number' => "SEP-IND-{$sequence}",
            'case_sequence' => $sequence,
            'status' => CaseStatus::Completed,
            'month' => '2026/06',
            'is_valid' => true,
            'is_cancelled' => false,
        ], $attributes));
    }
}
