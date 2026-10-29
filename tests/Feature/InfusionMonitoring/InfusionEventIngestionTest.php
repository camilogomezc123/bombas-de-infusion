<?php

namespace Tests\Feature\InfusionMonitoring;

use App\Enums\ProgramRole;
use App\Models\ClinicalProgram;
use App\Models\DrugInfusionProfile;
use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;
use App\Models\InfusionDoseSummary;
use App\Models\InfusionEvent;
use App\Models\Patient;
use App\Models\ProgramMember;
use App\Models\PumpDevice;
use App\Models\User;
use App\Models\VisScoreSnapshot;
use App\Notifications\InfusionCriticalAlertNotification;
use App\Services\Infusion\Connectors\FixturePumpConnector;
use App\Services\Infusion\IngestionResult;
use App\Services\Infusion\InfusionClinicalEngine;
use App\Services\Infusion\InfusionEventIngestionService;
use App\Services\InfusionIndicatorService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InfusionEventIngestionTest extends TestCase
{
    use RefreshDatabase;

    private function seedStay(string $caseNumber = 'BOMBAS-TEST-1', int $sequence = 1): IcuStay
    {
        $patient = Patient::query()->create(['identification' => $caseNumber, 'full_name' => 'Paciente Bombas']);

        $stay = IcuStay::query()->create([
            'clinical_program_id' => ClinicalProgram::query()->where('code', 'BOMBAS')->firstOrFail()->id,
            'patient_id' => $patient->id,
            'case_number' => $caseNumber,
            'case_sequence' => $sequence,
            'bed_label' => 'UCI ADULTOS',
            'status' => 'active',
            'admission_at' => CarbonImmutable::parse('2026-10-01 00:00:00'),
        ]);

        $stay->weights()->create(['weight_kg' => 70, 'recorded_at' => now(), 'source' => 'manual']);

        return $stay;
    }

    private function ingestFixture(): IngestionResult
    {
        return app(InfusionEventIngestionService::class)
            ->ingest(app(FixturePumpConnector::class)->fetchEvents(CarbonImmutable::parse('2026-10-01')));
    }

    public function test_ingests_fixture_events_resolves_stay_and_runs_clinical_engine(): void
    {
        $stay = $this->seedStay();

        $result = $this->ingestFixture();

        $this->assertSame(13, $result->events->count());
        $this->assertContains($stay->id, $result->touchedIcuStayIds);

        $vasopresinaEvents = InfusionEvent::query()->where('medication_name_raw', 'Vasopresina')->get();
        $this->assertCount(10, $vasopresinaEvents);
        $this->assertTrue($vasopresinaEvents->every(fn (InfusionEvent $e) => $e->icu_stay_id === $stay->id));
        $this->assertTrue($vasopresinaEvents->every(fn (InfusionEvent $e) => $e->drug_infusion_profile_id !== null));

        $pumpDevice = PumpDevice::query()->where('hospital_device_id', 'TB_UCIP6_9003511')->firstOrFail();
        $this->assertSame('UCI ADULTOS', $pumpDevice->current_bed_label);

        app(InfusionClinicalEngine::class)->process($result);

        $this->assertSame(3, InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)
            ->where('type', 'hard_limit_override')
            ->count());

        $snapshot = VisScoreSnapshot::query()->where('icu_stay_id', $stay->id)->latest('computed_at')->first();
        $this->assertNotNull($snapshot);
        $this->assertGreaterThan(0, (float) $snapshot->vis_score);

        $this->assertTrue(InfusionDoseSummary::query()->where('icu_stay_id', $stay->id)->exists());
    }

    public function test_critical_alerts_notify_active_program_members(): void
    {
        Notification::fake();

        $program = ClinicalProgram::query()->where('code', 'BOMBAS')->firstOrFail();
        $this->seedStay();

        $member = User::factory()->create();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $member->id,
            'role' => ProgramRole::Leader,
            'is_active' => true,
        ]);

        app(InfusionClinicalEngine::class)->process($this->ingestFixture());

        Notification::assertSentTo($member, InfusionCriticalAlertNotification::class);
    }

    public function test_cumulative_dose_threshold_creates_alert(): void
    {
        DrugInfusionProfile::query()->where('name', 'Vasopresina')
            ->update(['max_cumulative_dose_per_kg_per_day' => 0.00001]);

        $stay = $this->seedStay();

        app(InfusionClinicalEngine::class)->process($this->ingestFixture());

        $this->assertTrue(InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)
            ->where('type', 'max_dose_exceeded')
            ->exists());
    }

    public function test_indicator_service_reports_current_state(): void
    {
        $this->seedStay();

        app(InfusionClinicalEngine::class)->process($this->ingestFixture());

        $summary = app(InfusionIndicatorService::class)->summary();

        $this->assertSame(13, $summary['total_events']);
        $this->assertGreaterThan(0, $summary['hard_limit_override_rate_pct']);
        $this->assertNotNull($summary['avg_vis_score']);
    }
}
