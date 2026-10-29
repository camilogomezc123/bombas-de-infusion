<?php

namespace Tests\Feature\InfusionMonitoring;

use App\Enums\UserRole;
use App\Models\ClinicalProgram;
use App\Models\DrugInfusionProfile;
use App\Models\IcuPainAssessment;
use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;
use App\Models\InfusionEvent;
use App\Models\Patient;
use App\Models\PumpDevice;
use App\Models\User;
use App\Models\VisScoreSnapshot;
use App\Services\Infusion\OpioidPainAssessmentGuard;
use App\Services\Infusion\TherapeuticDuplicationGuard;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre la integración opioides <-> ICU Liberation (componente "A" del bundle ABCDEF),
 * la detección de duplicidad terapéutica, y las nuevas mejoras de usabilidad de la
 * Central de Monitoreo: resumen agregado, filtros, comparación contra valor basal y
 * reconocer alertas directamente desde el tile.
 */
class OpioidLiberationAndWallUxTest extends TestCase
{
    use RefreshDatabase;

    private function seedStay(string $caseNumber, string $bedLabel): IcuStay
    {
        $patient = Patient::query()->create(['identification' => $caseNumber, 'full_name' => "Paciente {$caseNumber}"]);

        $stay = IcuStay::query()->create([
            'clinical_program_id' => ClinicalProgram::query()->where('code', 'BOMBAS')->firstOrFail()->id,
            'patient_id' => $patient->id,
            'case_number' => $caseNumber,
            'case_sequence' => 1,
            'bed_label' => $bedLabel,
            'status' => 'active',
            'admission_at' => CarbonImmutable::parse('2026-10-01 00:00:00'),
        ]);

        $stay->weights()->create(['weight_kg' => 70, 'recorded_at' => now(), 'source' => 'manual']);

        return $stay;
    }

    private function seedDrugEvent(IcuStay $stay, string $drugName, CarbonImmutable $occurredAt, string $deviceSuffix = ''): InfusionEvent
    {
        $device = PumpDevice::query()->create([
            'hospital_device_id' => 'DEV-'.$stay->case_number.$deviceSuffix,
            'current_bed_label' => $stay->bed_label,
            'is_active' => true,
        ]);

        $profile = DrugInfusionProfile::query()->where('name', $drugName)->firstOrFail();

        return InfusionEvent::query()->create([
            'pump_device_id' => $device->id,
            'icu_stay_id' => $stay->id,
            'drug_infusion_profile_id' => $profile->id,
            'vendor_infusion_id' => $device->hospital_device_id.'-1',
            'occurred_at' => $occurredAt,
            'medication_name_raw' => $drugName,
            'concentration_raw' => '1 g / 100 ml',
            'attempted_value' => 10,
            'attempted_unit' => 'ml/hr',
            'existing_dose_rate_value' => 10,
            'existing_dose_rate_unit' => 'ml/hr',
            'ingested_at' => now(),
        ]);
    }

    public function test_opioid_pain_assessment_guard_flags_gap_without_recent_assessment(): void
    {
        $stay = $this->seedStay('OPIOID-1', 'Cama 30');
        $this->seedDrugEvent($stay, 'Fentanilo', CarbonImmutable::now()->subHours(2));

        app(OpioidPainAssessmentGuard::class)->evaluate($stay);

        $this->assertTrue(InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)->where('type', 'pain_assessment_overdue')->exists());
    }

    public function test_opioid_pain_assessment_guard_does_not_flag_when_assessment_is_recent(): void
    {
        $stay = $this->seedStay('OPIOID-2', 'Cama 31');
        $this->seedDrugEvent($stay, 'Fentanilo', CarbonImmutable::now()->subHours(2));

        IcuPainAssessment::query()->create([
            'icu_stay_id' => $stay->id,
            'assessed_at' => now()->subHours(1),
            'scale' => 'cpot',
            'score' => 2,
        ]);

        app(OpioidPainAssessmentGuard::class)->evaluate($stay);

        $this->assertFalse(InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)->where('type', 'pain_assessment_overdue')->exists());
    }

    public function test_therapeutic_duplication_guard_flags_two_concurrent_opioids(): void
    {
        $stay = $this->seedStay('DUP-1', 'Cama 32');
        $this->seedDrugEvent($stay, 'Fentanilo', CarbonImmutable::now()->subMinutes(30), '-A');
        $this->seedDrugEvent($stay, 'Morfina', CarbonImmutable::now()->subMinutes(20), '-B');

        app(TherapeuticDuplicationGuard::class)->evaluate($stay);

        $this->assertTrue(InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)->where('type', 'therapeutic_duplication')->exists());
    }

    public function test_therapeutic_duplication_guard_does_not_flag_single_vasoactive(): void
    {
        $stay = $this->seedStay('DUP-2', 'Cama 33');
        $this->seedDrugEvent($stay, 'Norepinefrina', CarbonImmutable::now()->subMinutes(10));

        app(TherapeuticDuplicationGuard::class)->evaluate($stay);

        $this->assertFalse(InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)->where('type', 'therapeutic_duplication')->exists());
    }

    public function test_therapeutic_duplication_guard_allows_two_concurrent_vasoactives(): void
    {
        // Combinar vasopresores (ej. norepi + vasopresina) es terapia estándar en shock
        // refractario — no debe generar ruido como la duplicidad de opioides/sedantes.
        $stay = $this->seedStay('DUP-3', 'Cama 34');
        $this->seedDrugEvent($stay, 'Norepinefrina', CarbonImmutable::now()->subMinutes(30), '-A');
        $this->seedDrugEvent($stay, 'Vasopresina', CarbonImmutable::now()->subMinutes(20), '-B');

        app(TherapeuticDuplicationGuard::class)->evaluate($stay);

        $this->assertFalse(InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)->where('type', 'therapeutic_duplication')->exists());
    }

    public function test_monitoring_wall_shows_opioid_icon_and_header_summary(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stay = $this->seedStay('OPIOID-3', 'Cama 35');
        $this->seedDrugEvent($stay, 'Fentanilo', CarbonImmutable::now()->subMinutes(10));

        $response = $this->actingAs($admin)->get('/infusion-monitoring/central-monitoreo');

        $response->assertOk();
        $response->assertSee('💉');
        $response->assertSee('crítica(s)');
        $response->assertSee('estable(s)');
    }

    public function test_monitoring_wall_only_alerts_filter_hides_stable_tiles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stableStay = $this->seedStay('STABLE-1', 'Cama 36');
        $this->seedDrugEvent($stableStay, 'Fentanilo', CarbonImmutable::now()->subMinutes(5));

        $response = $this->actingAs($admin)->get('/infusion-monitoring/central-monitoreo');
        $response->assertSee('Cama 36');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\InfusionMonitoring\Pages\MonitoringWall::class)
            ->set('onlyAlerts', true)
            ->assertDontSee('Cama 36');
    }

    public function test_monitoring_wall_acknowledge_alert_updates_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stay = $this->seedStay('ACK-1', 'Cama 37');
        $event = $this->seedDrugEvent($stay, 'Fentanilo', CarbonImmutable::now()->subMinutes(5));

        $alert = InfusionClinicalAlert::query()->create([
            'icu_stay_id' => $stay->id,
            'infusion_event_id' => $event->id,
            'drug_infusion_profile_id' => $event->drug_infusion_profile_id,
            'type' => 'max_dose_exceeded',
            'severity' => 'warning',
            'message' => 'Alerta de prueba.',
            'status' => 'open',
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\InfusionMonitoring\Pages\MonitoringWall::class)
            ->call('acknowledgeAlert', $alert->id);

        $this->assertSame('acknowledged', $alert->refresh()->status);
        $this->assertSame($admin->id, $alert->acknowledged_by);
    }

    public function test_monitoring_wall_shows_baseline_vis_comparison(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stay = $this->seedStay('BASELINE-1', 'Cama 38');
        $this->seedDrugEvent($stay, 'Norepinefrina', CarbonImmutable::now()->subMinutes(5));

        VisScoreSnapshot::query()->create([
            'icu_stay_id' => $stay->id, 'computed_at' => now()->subDays(2),
            'vis_score' => 3.0, 'weight_kg_used' => 70,
        ]);
        VisScoreSnapshot::query()->create([
            'icu_stay_id' => $stay->id, 'computed_at' => now()->subMinutes(5),
            'vis_score' => 10.0, 'weight_kg_used' => 70,
        ]);

        $response = $this->actingAs($admin)->get('/infusion-monitoring/central-monitoreo');

        $response->assertOk();
        $response->assertSee('vs. ingreso');
        $response->assertSee('+7.0');
    }
}
