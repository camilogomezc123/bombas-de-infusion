<?php

namespace Tests\Feature\InfusionMonitoring;

use App\Enums\UserRole;
use App\Models\ClinicalProgram;
use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;
use App\Models\Patient;
use App\Models\PumpDevice;
use App\Models\User;
use App\Services\Infusion\Connectors\FixturePumpConnector;
use App\Services\Infusion\InfusionClinicalEngine;
use App\Services\Infusion\InfusionEventIngestionService;
use App\Services\InfusionIndicatorService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre las funciones agregadas inspiradas en centrales comerciales (Fresenius Agilia
 * Link+ / B.Braun OnlineSuite / BD HealthSight): estado operativo del dispositivo,
 * multicanal, cumplimiento de librería de dosis, panel de alarmas por turno y
 * auditoría de anulaciones por operador.
 */
class PumpTelemetryFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function seedStay(): IcuStay
    {
        $patient = Patient::query()->create(['identification' => 'TELE-1', 'full_name' => 'Paciente Telemetría']);

        $stay = IcuStay::query()->create([
            'clinical_program_id' => ClinicalProgram::query()->where('code', 'BOMBAS')->firstOrFail()->id,
            'patient_id' => $patient->id,
            'case_number' => 'TELE-1',
            'case_sequence' => 1,
            'bed_label' => 'UCI ADULTOS',
            'status' => 'active',
            'admission_at' => CarbonImmutable::parse('2026-10-01 00:00:00'),
        ]);

        $stay->weights()->create(['weight_kg' => 70, 'recorded_at' => now(), 'source' => 'manual']);

        return $stay;
    }

    private function ingestAndProcess(): void
    {
        $result = app(InfusionEventIngestionService::class)
            ->ingest(app(FixturePumpConnector::class)->fetchEvents(CarbonImmutable::parse('2026-10-01')));

        app(InfusionClinicalEngine::class)->process($result);
    }

    public function test_device_alarm_guard_creates_and_dedupes_occlusion_alert(): void
    {
        $stay = $this->seedStay();

        $this->ingestAndProcess();

        $occlusionAlerts = InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)
            ->where('type', 'device_alarm')
            ->where('message', 'like', '%Oclusión%')
            ->get();

        // Tres eventos "PUMP - Duro" con device_status=occlusion comparten la misma
        // bomba/canal/fármaco: debe quedar una sola alerta abierta, no tres.
        $this->assertCount(1, $occlusionAlerts);
        $this->assertSame('critical', $occlusionAlerts->first()->severity);

        $batteryAlerts = InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)
            ->where('type', 'device_alarm')
            ->where('message', 'like', '%Batería baja%')
            ->get();

        $this->assertCount(1, $batteryAlerts);
        $this->assertSame('warning', $batteryAlerts->first()->severity);
    }

    public function test_pump_device_telemetry_is_updated_from_ingestion(): void
    {
        $this->seedStay();
        $this->ingestAndProcess();

        $device = PumpDevice::query()->where('hospital_device_id', 'TB_UCIP6_9003511')->firstOrFail();

        $this->assertSame(2, $device->channel_count);
        $this->assertNotNull($device->status);
        $this->assertNotNull($device->battery_pct);
    }

    public function test_indicator_service_reports_library_compliance(): void
    {
        $this->seedStay();
        $this->ingestAndProcess();

        $summary = app(InfusionIndicatorService::class)->summary();

        $this->assertArrayHasKey('library_compliance_pct', $summary);
        $this->assertLessThan(100, $summary['library_compliance_pct']);
        $this->assertGreaterThan(0, $summary['library_compliance_pct']);
    }

    public function test_monitoring_wall_shows_two_channels_for_multichannel_device(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $this->seedStay();
        $this->ingestAndProcess();

        $response = $this->actingAs($admin)->get('/infusion-monitoring/central-monitoreo');

        $response->assertOk();
        $response->assertSee('Canal 2');
        $response->assertSee('Norepinefrina');
        $response->assertSee('sin librería');
    }

    public function test_alarm_insights_page_renders_grouped_rows(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $this->seedStay();
        $this->ingestAndProcess();

        $response = $this->actingAs($admin)->get('/infusion-monitoring/alarmas');

        $response->assertOk();
        $response->assertSee('UCI ADULTOS');
    }

    public function test_override_audit_page_lists_operator_rows(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $this->seedStay();
        $this->ingestAndProcess();

        $response = $this->actingAs($admin)->get('/infusion-monitoring/auditoria-anulaciones');

        $response->assertOk();
        $response->assertSee('ENF-1042');
    }

    public function test_unit_trends_page_renders(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $this->seedStay();
        $this->ingestAndProcess();

        $response = $this->actingAs($admin)->get('/infusion-monitoring/tendencia-unidad');

        $response->assertOk();
    }

    public function test_pump_device_and_event_resource_lists_render_with_new_columns(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $this->seedStay();
        $this->ingestAndProcess();

        $this->actingAs($admin)->get('/infusion-monitoring/pump-devices')->assertOk();
        $this->actingAs($admin)->get('/infusion-monitoring/infusion-events')->assertOk();
    }
}
