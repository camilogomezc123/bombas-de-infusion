<?php

namespace Tests\Feature;

use App\Enums\CaseStatus;
use App\Filament\Pages\Hub;
use App\Models\AcvCase;
use App\Models\Patient;
use App\Models\SepsisCase;
use App\Services\SepsisIndicatorService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class HubAndDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_hub_cards_show_active_centers_with_one_query_per_clinical_registry(): void
    {
        CarbonImmutable::setTestNow('2026-06-15 10:00:00');
        $patient = Patient::query()->create([
            'identification' => 'HUB-1',
            'full_name' => 'Paciente Hub',
        ]);

        $this->createAcvCase($patient, 1, CaseStatus::Hospitalized, '2026/06');
        $this->createAcvCase($patient, 2, CaseStatus::Completed, '2026/06');
        $this->createAcvCase($patient, 3, CaseStatus::Completed, '2026/05');
        $this->createAcvCase($patient, 4, CaseStatus::Completed, '2026/06', true);

        $this->createSepsisCase($patient, 1, CaseStatus::Hospitalized, '2026/06');
        $this->createSepsisCase($patient, 2, CaseStatus::Completed, '2026/05');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $centers = (new Hub)->centers();
        $queries = DB::getQueryLog();

        $this->assertSame([
            ['label' => 'Total', 'value' => 3],
            ['label' => 'Hospitalizados', 'value' => 1],
            ['label' => 'Este mes', 'value' => 2],
        ], $centers[0]['stats']);
        $this->assertSame([
            ['label' => 'Total', 'value' => 2],
            ['label' => 'Hospitalizados', 'value' => 1],
            ['label' => 'Este mes', 'value' => 1],
        ], $centers[1]['stats']);
        $this->assertTrue($centers[2]['available']);
        $this->assertSame('Infarto', $centers[2]['name']);
        $this->assertTrue($centers[3]['available']);
        $this->assertSame('TEP', $centers[3]['name']);
        $this->assertTrue($centers[4]['available']);
        $this->assertSame('ICU Liberation', $centers[4]['name']);
        $this->assertTrue($centers[5]['available']);
        $this->assertSame('PICS', $centers[5]['name']);
        $this->assertCount(6, $queries);

        DB::flushQueryLog();
        $cachedCenters = (new Hub)->centers();

        $this->assertSame($centers[0]['stats'], $cachedCenters[0]['stats']);
        $this->assertCount(0, DB::getQueryLog());

        $this->createAcvCase($patient, 5, CaseStatus::Completed, '2026/06');
        DB::flushQueryLog();

        $refreshedCenters = (new Hub)->centers();

        $this->assertSame(4, $refreshedCenters[0]['stats'][0]['value']);
        $this->assertSame(3, $refreshedCenters[0]['stats'][2]['value']);
        $this->assertCount(6, DB::getQueryLog());
    }

    public function test_sepsis_dashboard_builds_all_sections_with_two_queries(): void
    {
        $patient = Patient::query()->create([
            'identification' => 'SEPSIS-1',
            'full_name' => 'Paciente Sepsis',
        ]);

        $this->createSepsisCase($patient, 1, CaseStatus::Completed, '2026/05');
        $this->createSepsisCase($patient, 2, CaseStatus::Completed, '2026/06');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $dashboard = app(SepsisIndicatorService::class)->dashboard('2026');
        $queries = DB::getQueryLog();

        $this->assertCount(2, $dashboard['rows']);
        $this->assertSame(2, $dashboard['current']['total']);
        $this->assertArrayHasKey('focus', $dashboard['distributions']);
        $this->assertCount(2, $dashboard['cases']);
        // Casos + eager load de pacientes para el detalle por paciente.
        $this->assertCount(2, $queries);
    }

    public function test_panel_logos_link_to_the_hub_and_global_search_is_disabled(): void
    {
        foreach (['admin', 'acv', 'sepsis', 'infarto', 'tep', 'icu-liberation', 'pics'] as $panelId) {
            $panel = filament()->getPanel($panelId);

            $this->assertTrue(Str::endsWith($panel->getHomeUrl(), '/admin'));
            $this->assertNull($panel->getGlobalSearchProvider());
        }
    }

    private function createAcvCase(
        Patient $patient,
        int $sequence,
        CaseStatus $status,
        string $month,
        bool $cancelled = false,
    ): void {
        AcvCase::query()->create([
            'patient_id' => $patient->id,
            'case_number' => "ACV-HUB-{$sequence}",
            'case_sequence' => $sequence,
            'status' => $status,
            'month' => $month,
            'is_cancelled' => $cancelled,
        ]);
    }

    private function createSepsisCase(
        Patient $patient,
        int $sequence,
        CaseStatus $status,
        string $month,
    ): void {
        SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'case_number' => "SEP-HUB-{$sequence}",
            'case_sequence' => $sequence,
            'status' => $status,
            'month' => $month,
            'is_valid' => true,
            'is_cancelled' => false,
        ]);
    }
}
