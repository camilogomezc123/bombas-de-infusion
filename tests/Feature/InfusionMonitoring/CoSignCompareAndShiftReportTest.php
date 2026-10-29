<?php

namespace Tests\Feature\InfusionMonitoring;

use App\Enums\UserRole;
use App\Models\ClinicalProgram;
use App\Models\DrugInfusionProfile;
use App\Models\IcuStay;
use App\Models\InfusionEvent;
use App\Models\Patient;
use App\Models\PumpDevice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cubre las últimas mejoras pedidas: doble verificación (co-firma) de fármacos de alto
 * riesgo, comparación lado a lado de dos pacientes, sonido de alerta (vía la propiedad
 * entangled que lo dispara) y el reporte de cambio de turno.
 */
class CoSignCompareAndShiftReportTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    private function seedStay(string $caseNumber, string $bedLabel): IcuStay
    {
        $patient = Patient::query()->create(['identification' => $caseNumber, 'full_name' => "Paciente {$caseNumber}"]);

        $stay = IcuStay::query()->create([
            'clinical_program_id' => ClinicalProgram::query()->where('code', 'BOMBAS')->firstOrFail()->id,
            'patient_id' => $patient->id,
            'case_number' => $caseNumber,
            'case_sequence' => ++self::$sequence,
            'bed_label' => $bedLabel,
            'status' => 'active',
            'admission_at' => CarbonImmutable::parse('2026-10-01 00:00:00'),
        ]);

        $stay->weights()->create(['weight_kg' => 70, 'recorded_at' => now(), 'source' => 'manual']);

        return $stay;
    }

    private function seedDrugEvent(IcuStay $stay, string $drugName, CarbonImmutable $occurredAt): InfusionEvent
    {
        $device = PumpDevice::query()->create([
            'hospital_device_id' => 'DEV-'.$stay->case_number,
            'current_bed_label' => $stay->bed_label,
            'is_active' => true,
        ]);

        $profile = DrugInfusionProfile::query()->where('name', $drugName)->firstOrFail();

        return InfusionEvent::query()->create([
            'pump_device_id' => $device->id,
            'icu_stay_id' => $stay->id,
            'drug_infusion_profile_id' => $profile->id,
            'occurred_at' => $occurredAt,
            'medication_name_raw' => $drugName,
            'attempted_value' => 10,
            'attempted_unit' => 'ml/hr',
            'existing_dose_rate_value' => 10,
            'existing_dose_rate_unit' => 'ml/hr',
            'ingested_at' => now(),
        ]);
    }

    public function test_high_risk_event_requires_co_sign_and_can_be_co_signed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stay = $this->seedStay('COSIGN-1', 'Cama 40');
        $event = $this->seedDrugEvent($stay, 'Norepinefrina', CarbonImmutable::now());

        $this->assertTrue($event->requiresCoSign());
        $this->assertNull($event->co_signed_at);

        $event->update(['co_signed_by' => $admin->id, 'co_signed_at' => now()]);

        $this->assertNotNull($event->refresh()->co_signed_at);
        $this->assertSame($admin->id, $event->co_signed_by);
    }

    public function test_antibiotic_event_does_not_require_co_sign(): void
    {
        $stay = $this->seedStay('COSIGN-2', 'Cama 41');
        $event = $this->seedDrugEvent($stay, 'Meropenem', CarbonImmutable::now());

        $this->assertFalse($event->requiresCoSign());
    }

    public function test_indicator_service_reports_co_sign_compliance(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stayA = $this->seedStay('COSIGN-3', 'Cama 42');
        $eventA = $this->seedDrugEvent($stayA, 'Norepinefrina', CarbonImmutable::now());
        $eventA->update(['co_signed_by' => $admin->id, 'co_signed_at' => now()]);

        $stayB = $this->seedStay('COSIGN-4', 'Cama 43');
        $this->seedDrugEvent($stayB, 'Fentanilo', CarbonImmutable::now());

        $summary = app(\App\Services\InfusionIndicatorService::class)->summary();

        $this->assertSame(50.0, $summary['co_sign_compliance_pct']);
    }

    public function test_infusion_event_resource_shows_cosign_action_for_high_risk_pending(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stay = $this->seedStay('COSIGN-5', 'Cama 44');
        $this->seedDrugEvent($stay, 'Norepinefrina', CarbonImmutable::now());

        $response = $this->actingAs($admin)->get('/infusion-monitoring/infusion-events');

        $response->assertOk();
        $response->assertSee('Pendiente');
    }

    public function test_monitoring_wall_compare_toggle_shows_two_tiles_side_by_side(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stayA = $this->seedStay('CMP-1', 'Cama 45');
        $this->seedDrugEvent($stayA, 'Norepinefrina', CarbonImmutable::now());
        $stayB = $this->seedStay('CMP-2', 'Cama 46');
        $this->seedDrugEvent($stayB, 'Vasopresina', CarbonImmutable::now());

        Livewire::actingAs($admin)
            ->test(\App\Filament\InfusionMonitoring\Pages\MonitoringWall::class)
            ->call('toggleCompare', $stayA->id)
            ->call('toggleCompare', $stayB->id)
            ->assertSee('Comparación VIS lado a lado')
            ->assertSee('Cama 45')
            ->assertSee('Cama 46');
    }

    public function test_monitoring_wall_compare_toggle_caps_at_two_and_drops_oldest(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stayA = $this->seedStay('CMP-3', 'Cama 47');
        $this->seedDrugEvent($stayA, 'Norepinefrina', CarbonImmutable::now());
        $stayB = $this->seedStay('CMP-4', 'Cama 48');
        $this->seedDrugEvent($stayB, 'Vasopresina', CarbonImmutable::now());
        $stayC = $this->seedStay('CMP-5', 'Cama 49');
        $this->seedDrugEvent($stayC, 'Dopamina', CarbonImmutable::now());

        $component = Livewire::actingAs($admin)
            ->test(\App\Filament\InfusionMonitoring\Pages\MonitoringWall::class)
            ->call('toggleCompare', $stayA->id)
            ->call('toggleCompare', $stayB->id)
            ->call('toggleCompare', $stayC->id);

        $component->assertSet('compareIds', [$stayB->id, $stayC->id]);
    }

    public function test_shift_handoff_report_renders_and_lists_drugs_for_window(): void
    {
        // Congela la hora dentro del turno de día (07-19h) por defecto de la página,
        // para que el test no dependa de a qué hora real se ejecute la suite.
        $this->travelTo(CarbonImmutable::parse('2026-10-29 10:00:00'));

        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stay = $this->seedStay('SHIFT-1', 'Cama 50');
        $this->seedDrugEvent($stay, 'Norepinefrina', CarbonImmutable::now());

        $response = $this->actingAs($admin)->get('/infusion-monitoring/reporte-turno');

        $response->assertOk();
        $response->assertSee('Cama 50');
        $response->assertSee('Norepinefrina');
    }
}
