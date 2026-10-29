<?php

namespace Tests\Feature\InfusionMonitoring;

use App\Enums\UserRole;
use App\Models\ClinicalProgram;
use App\Models\DrugInfusionProfile;
use App\Models\IcuStay;
use App\Models\InfusionClinicalAlert;
use App\Models\InfusionEvent;
use App\Models\Patient;
use App\Models\PumpDevice;
use App\Models\User;
use App\Models\VisScoreSnapshot;
use App\Services\Infusion\AntibioticContinuityGuard;
use App\Services\Infusion\MaxDoseGuard;
use App\Services\InfusionIndicatorService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre las mejoras pedidas sobre la Central de Monitoreo: más tendencia (delta de
 * VIS), alertas propias de cada medicamento en infusión (no solo un contador agregado
 * por paciente), y antibióticos en infusión continua/extendida de código sepsis
 * (piperacilina-tazobactam, meropenem, vancomicina) con alerta de interrupción cuando
 * la bomba deja de reportar telemetría más allá del umbral de continuidad del fármaco.
 */
class MedicationAlertsAndContinuityTest extends TestCase
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

    private function seedAntibioticEvent(IcuStay $stay, string $drugName, CarbonImmutable $occurredAt): InfusionEvent
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

    public function test_antibiotic_continuity_guard_flags_interruption_when_silent_too_long(): void
    {
        $stay = $this->seedStay('ANTIB-1', 'Cama 10');
        $this->seedAntibioticEvent($stay, 'Meropenem', CarbonImmutable::now()->subHours(3));

        app(AntibioticContinuityGuard::class)->evaluate($stay);

        $this->assertTrue(InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)
            ->where('type', 'antibiotic_interruption')
            ->where('status', 'open')
            ->exists());
    }

    public function test_antibiotic_continuity_guard_does_not_flag_recent_telemetry(): void
    {
        $stay = $this->seedStay('ANTIB-2', 'Cama 11');
        $this->seedAntibioticEvent($stay, 'Meropenem', CarbonImmutable::now()->subMinutes(5));

        app(AntibioticContinuityGuard::class)->evaluate($stay);

        $this->assertFalse(InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)
            ->where('type', 'antibiotic_interruption')
            ->exists());
    }

    public function test_antibiotic_continuity_guard_dedupes_open_alert_on_repeated_runs(): void
    {
        $stay = $this->seedStay('ANTIB-3', 'Cama 12');
        $this->seedAntibioticEvent($stay, 'Vancomicina', CarbonImmutable::now()->subHours(5));

        app(AntibioticContinuityGuard::class)->evaluate($stay);
        app(AntibioticContinuityGuard::class)->evaluate($stay);
        app(AntibioticContinuityGuard::class)->evaluate($stay);

        $this->assertSame(1, InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)
            ->where('type', 'antibiotic_interruption')
            ->count());
    }

    public function test_check_antibiotic_continuity_command_flags_silent_stay(): void
    {
        $stay = $this->seedStay('ANTIB-4', 'Cama 13');
        $this->seedAntibioticEvent($stay, 'Piperacilina/Tazobactam', CarbonImmutable::now()->subHours(4));

        $this->artisan('infusion:check-antibiotic-continuity')->assertSuccessful();

        $this->assertTrue(InfusionClinicalAlert::query()
            ->where('icu_stay_id', $stay->id)
            ->where('type', 'antibiotic_interruption')
            ->exists());
    }

    public function test_indicator_service_reports_open_antibiotic_interruptions(): void
    {
        $stay = $this->seedStay('ANTIB-5', 'Cama 14');
        $this->seedAntibioticEvent($stay, 'Meropenem', CarbonImmutable::now()->subHours(3));

        app(AntibioticContinuityGuard::class)->evaluate($stay);

        $summary = app(InfusionIndicatorService::class)->summary();

        $this->assertSame(1, $summary['open_antibiotic_interruptions']);
    }

    public function test_max_dose_guard_flags_possible_unit_error_for_grossly_implausible_rate(): void
    {
        $stay = $this->seedStay('UNITERR-1', 'Cama 20');
        $stay->update(['status' => 'active']);

        $device = PumpDevice::query()->create(['hospital_device_id' => 'DEV-UNITERR-1', 'current_bed_label' => 'Cama 20', 'is_active' => true]);
        $profile = DrugInfusionProfile::query()->where('name', 'Epinefrina')->firstOrFail();

        // 250000 mcg/hr ÷ 70 kg ≈ 59.5 mcg/kg/min — ~60x el límite duro (1 mcg/kg/min):
        // exactamente el tipo de dato que motivó este guardián (el bug real que corrigió
        // esta sesión usaba ese mismo valor implausible en el seeder de demostración).
        $event = InfusionEvent::query()->create([
            'pump_device_id' => $device->id,
            'icu_stay_id' => $stay->id,
            'drug_infusion_profile_id' => $profile->id,
            'occurred_at' => now(),
            'medication_name_raw' => 'Epinefrina',
            'concentration_raw' => '4 mg / 250 ml',
            'attempted_value' => 250000,
            'attempted_unit' => 'mcg/hr',
            'existing_dose_rate_value' => 250000,
            'existing_dose_rate_unit' => 'mcg/hr',
            'ingested_at' => now(),
        ]);

        app(MaxDoseGuard::class)->evaluate($event);

        $alert = InfusionClinicalAlert::query()->where('icu_stay_id', $stay->id)->first();

        $this->assertNotNull($alert);
        $this->assertSame('possible_unit_error', $alert->type);
        $this->assertSame('critical', $alert->severity);
        $this->assertFalse(InfusionClinicalAlert::query()->where('type', 'max_dose_exceeded')->exists());
    }

    public function test_max_dose_guard_still_flags_ordinary_overage_as_max_dose_exceeded(): void
    {
        $stay = $this->seedStay('UNITERR-2', 'Cama 21');

        $device = PumpDevice::query()->create(['hospital_device_id' => 'DEV-UNITERR-2', 'current_bed_label' => 'Cama 21', 'is_active' => true]);
        $profile = DrugInfusionProfile::query()->where('name', 'Epinefrina')->firstOrFail();

        // 5000 mcg/hr ÷ 70 kg ≈ 1.19 mcg/kg/min — apenas por encima del límite duro
        // (1 mcg/kg/min), no una magnitud implausible.
        $event = InfusionEvent::query()->create([
            'pump_device_id' => $device->id,
            'icu_stay_id' => $stay->id,
            'drug_infusion_profile_id' => $profile->id,
            'occurred_at' => now(),
            'medication_name_raw' => 'Epinefrina',
            'concentration_raw' => '4 mg / 250 ml',
            'attempted_value' => 5000,
            'attempted_unit' => 'mcg/hr',
            'existing_dose_rate_value' => 5000,
            'existing_dose_rate_unit' => 'mcg/hr',
            'ingested_at' => now(),
        ]);

        app(MaxDoseGuard::class)->evaluate($event);

        $alert = InfusionClinicalAlert::query()->where('icu_stay_id', $stay->id)->first();

        $this->assertNotNull($alert);
        $this->assertSame('max_dose_exceeded', $alert->type);
    }

    public function test_monitoring_wall_shows_vis_trend_arrow_and_per_drug_alert_badge(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'is_active' => true]);
        $stay = $this->seedStay('TREND-1', 'Cama 15');

        $event = $this->seedAntibioticEvent($stay, 'Vancomicina', CarbonImmutable::now()->subMinutes(5));

        VisScoreSnapshot::query()->create([
            'icu_stay_id' => $stay->id, 'computed_at' => now()->subHours(6),
            'vis_score' => 5.0, 'weight_kg_used' => 70,
        ]);
        VisScoreSnapshot::query()->create([
            'icu_stay_id' => $stay->id, 'computed_at' => now()->subHours(1),
            'vis_score' => 9.0, 'weight_kg_used' => 70,
        ]);

        InfusionClinicalAlert::query()->create([
            'icu_stay_id' => $stay->id,
            'infusion_event_id' => $event->id,
            'drug_infusion_profile_id' => $event->drug_infusion_profile_id,
            'type' => 'max_dose_exceeded',
            'severity' => 'warning',
            'message' => 'Alerta de prueba ligada al fármaco.',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->get('/infusion-monitoring/central-monitoreo');

        $response->assertOk();
        // Delta de VIS (9.0 - 5.0 = 4.0) debe verse como tendencia al alza.
        $response->assertSee('▲');
        $response->assertSee('4.0');
        // La alerta debe verse junto al canal de Vancomicina, no solo como contador agregado.
        $response->assertSee('Dosis máxima institucional excedida');
    }
}
