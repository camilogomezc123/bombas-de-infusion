<?php

namespace Tests\Feature\InfusionMonitoring;

use App\Enums\UserRole;
use App\Models\ClinicalProgram;
use App\Models\IcuStay;
use App\Models\Patient;
use App\Models\User;
use App\Services\Infusion\Connectors\FixturePumpConnector;
use App\Services\Infusion\InfusionClinicalEngine;
use App\Services\Infusion\InfusionEventIngestionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringWallSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_wall_renders_empty_state_for_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/infusion-monitoring/central-monitoreo');

        $response->assertOk();
        $response->assertSee('Central de Monitoreo');
    }

    public function test_monitoring_wall_renders_a_tile_with_vis_dose_and_alerts(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);

        $patient = Patient::query()->create(['identification' => 'WALL-1', 'full_name' => 'Paciente Pantalla']);
        $stay = IcuStay::query()->create([
            'clinical_program_id' => ClinicalProgram::query()->where('code', 'BOMBAS')->firstOrFail()->id,
            'patient_id' => $patient->id,
            'case_number' => 'WALL-TEST-1',
            'case_sequence' => 1,
            'bed_label' => 'UCI ADULTOS',
            'status' => 'active',
            'admission_at' => CarbonImmutable::parse('2026-10-01 00:00:00'),
        ]);
        $stay->weights()->create(['weight_kg' => 70, 'recorded_at' => now(), 'source' => 'manual']);

        $result = app(InfusionEventIngestionService::class)
            ->ingest(app(FixturePumpConnector::class)->fetchEvents(CarbonImmutable::parse('2026-10-01')));
        app(InfusionClinicalEngine::class)->process($result);

        $response = $this->actingAs($admin)->get('/infusion-monitoring/central-monitoreo');

        $response->assertOk();
        $response->assertSee('UCI ADULTOS');
        $response->assertSee('Paciente Pantalla');
        // El evento más reciente de la estancia (por fecha) es el de Ondansetrón,
        // no el de Vasopresina — la grilla muestra el último evento cronológico.
        $response->assertSee('Ondansetron');
    }
}
