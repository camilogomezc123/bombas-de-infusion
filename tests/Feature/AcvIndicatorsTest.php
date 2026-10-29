<?php

namespace Tests\Feature;

use App\Enums\CaseStatus;
use App\Filament\Acv\Pages\AcvIndicators;
use App\Models\AcvCase;
use App\Models\Patient;
use App\Services\AcvIndicatorService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcvIndicatorsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_selects_the_previous_calendar_month_by_default(): void
    {
        CarbonImmutable::setTestNow('2026-01-15 12:00:00');

        $page = new AcvIndicators;
        $page->mount();

        $this->assertSame('2025', $page->year);
        $this->assertSame('2025/12', $page->month);
        $this->assertSame('general', $page->dashboardView);
    }

    public function test_it_supports_quick_period_and_dashboard_view_selection(): void
    {
        $patient = Patient::query()->create([
            'identification' => '321',
            'full_name' => 'Paciente selector',
        ]);
        $this->createCase($patient, 1, '2025/01', 30);

        $page = new AcvIndicators;
        $page->year = '2025';

        $page->selectPeriod('q1');
        $page->selectView('general');

        $this->assertSame('q1', $page->month);
        $this->assertSame('general', $page->dashboardView);

        $page->selectPeriod('invalid');
        $page->selectView('invalid');

        $this->assertSame('q1', $page->month);
        $this->assertSame('general', $page->dashboardView);
    }

    public function test_it_only_exposes_quarters_with_data_for_the_selected_year(): void
    {
        $patient = Patient::query()->create([
            'identification' => '654',
            'full_name' => 'Paciente períodos',
        ]);
        $this->createCase($patient, 1, '2026/01', 30);
        $this->createCase($patient, 2, '2026/06', 30);

        $page = new AcvIndicators;
        $page->year = '2026';

        $this->assertSame(
            ['all', 'q1', 'q2'],
            array_keys($page->quickPeriods()),
        );
    }

    public function test_it_calculates_an_annual_summary_from_all_valid_cases_in_the_year(): void
    {
        $patient = Patient::query()->create([
            'identification' => '123',
            'full_name' => 'Paciente prueba',
        ]);

        $this->createCase($patient, 1, '2025/01', 30);
        $this->createCase($patient, 2, '2025/02', 90);
        $this->createCase($patient, 3, '2025/03', 10, true);
        $this->createCase($patient, 4, '2024/12', 20);

        $summary = app(AcvIndicatorService::class)->annual('2025');

        $this->assertNotNull($summary);
        $this->assertSame(2, $summary['total']);
        $this->assertSame(2, $summary['thrombolysis']);
        $this->assertSame(60, $summary['door_needle']);
        $this->assertSame(50.0, $summary['needle_60']);
    }

    public function test_it_calculates_a_quarter_from_its_three_months(): void
    {
        $patient = Patient::query()->create([
            'identification' => '456',
            'full_name' => 'Paciente trimestral',
        ]);

        $this->createCase($patient, 1, '2025/01', 30);
        $this->createCase($patient, 2, '2025/03', 90);
        $this->createCase($patient, 3, '2025/04', 10);

        $summary = app(AcvIndicatorService::class)->quarterly('2025', 1);

        $this->assertNotNull($summary);
        $this->assertSame(2, $summary['total']);
        $this->assertSame(60, $summary['door_needle']);
        $this->assertSame(50.0, $summary['needle_60']);
    }

    public function test_it_calculates_resq_and_diagnostic_distribution_metrics(): void
    {
        $patient = Patient::query()->create([
            'identification' => '789',
            'full_name' => 'Paciente RES-Q',
        ]);

        $ischemic = $this->createCase($patient, 1, '2025/01', 30);
        $ischemic->update([
            'thrombectomy' => true,
            'groin_puncture_at' => $ischemic->arrival_at->addMinutes(80),
            'clinical_data' => ['tici' => '2b', 'imaging_type' => 'TAC'],
        ]);

        $this->createCase($patient, 2, '2025/01', 90, strokeType: 'Hemorragia intracerebral');
        $this->createCase($patient, 3, '2025/01', 20, strokeType: 'Ataque isquémico transitorio (AIT)');
        $this->createCase($patient, 4, '2025/01', 20, strokeType: 'imitador del Ictus');
        $this->createCase($patient, 5, '2025/01', 20, strokeType: 'Trombosis venosa cerebral');

        $summary = app(AcvIndicatorService::class)->monthly('2025')[0];

        $this->assertSame(1, $summary['ischemic']);
        $this->assertSame(1, $summary['hemorrhagic']);
        $this->assertSame(1, $summary['tia']);
        $this->assertSame(1, $summary['mimics']);
        $this->assertSame(1, $summary['venous_thrombosis']);
        $this->assertSame(100.0, $summary['successful_recanalization']);
        $this->assertSame(20.0, $summary['imaging_compliance']);
    }

    public function test_it_calculates_angels_award_metrics_with_the_resq_denominators(): void
    {
        $patient = Patient::query()->create([
            'identification' => '987',
            'full_name' => 'Paciente Angels',
        ]);

        $first = $this->createCase($patient, 1, '2025/02', 30);
        $first->update(['speech_therapy_at' => $first->arrival_at->addHours(2)]);
        $this->createCase($patient, 2, '2025/02', 50);
        $this->createCase($patient, 3, '2025/02', 70);
        $inpatient = $this->createCase($patient, 4, '2025/02', 20);
        $inpatient->update(['clinical_data' => ['inpatient_stroke' => true]]);
        $hemorrhagic = $this->createCase(
            $patient,
            5,
            '2025/02',
            20,
            strokeType: 'Hemorragia intracerebral',
        );
        $hemorrhagic->update(['speech_therapy_at' => $hemorrhagic->arrival_at->addHours(3)]);

        $summary = app(AcvIndicatorService::class)->monthly('2025')[0];
        $metrics = collect($summary['angels_metrics'])->keyBy('key');

        $needle60 = $metrics->get('door_to_needle_60');
        $this->assertSame(3, $needle60['total_cases']);
        $this->assertSame(2, $needle60['eligible_cases']);
        $this->assertSame(66.7, $needle60['value']);
        $this->assertSame('Oro', $needle60['status']);

        $recanalization = $metrics->get('recanalization_rate');
        $this->assertSame(4, $recanalization['total_cases']);
        $this->assertSame(4, $recanalization['eligible_cases']);
        $this->assertSame('Diamante', $recanalization['status']);

        $dysphagia = $metrics->get('dysphagia_screening');
        $this->assertSame(5, $dysphagia['total_cases']);
        $this->assertSame(2, $dysphagia['eligible_cases']);
        $this->assertSame(40.0, $dysphagia['value']);

        $this->assertFalse($metrics->get('af_anticoagulants')['available']);
        $this->assertNull($metrics->get('af_anticoagulants')['value']);
    }

    private function createCase(
        Patient $patient,
        int $sequence,
        string $month,
        int $doorNeedleMinutes,
        bool $cancelled = false,
        string $strokeType = 'Isquémico',
    ): AcvCase {
        $arrival = CarbonImmutable::parse($month.'/01 08:00', 'America/Bogota');

        return AcvCase::query()->create([
            'patient_id' => $patient->id,
            'case_number' => 'CASE-'.$sequence,
            'case_sequence' => $sequence,
            'status' => CaseStatus::Imported,
            'month' => $month,
            'stroke_type' => $strokeType,
            'arrival_at' => $arrival,
            'thrombolysis_at' => $arrival->addMinutes($doorNeedleMinutes),
            'thrombolysed' => true,
            'is_cancelled' => $cancelled,
        ]);
    }
}
