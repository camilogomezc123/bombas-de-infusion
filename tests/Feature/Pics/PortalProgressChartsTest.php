<?php

namespace Tests\Feature\Pics;

use App\Models\ClinicalProgram;
use App\Models\HomeMonitoringReading;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\PicsFollowup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalProgressChartsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('PROG-'), 'full_name' => 'Progreso']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_shows_an_empty_state_with_fewer_than_two_readings(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'prog-patient@test.com', 'must_change_password' => false]);

        HomeMonitoringReading::query()->create([
            'pics_case_id' => $case->id, 'reading_type' => 'spo2', 'value' => 97, 'unit' => '%',
            'measured_at' => now(), 'recorded_by_type' => Patient::class, 'recorded_by_id' => $patient->id,
        ]);

        $this->actingAs($patient, 'patient')
            ->get('/portal/progreso')
            ->assertOk()
            ->assertSee('Todavía no tienes suficientes registros');
    }

    public function test_shows_a_monitoring_chart_with_two_or_more_readings_of_the_same_type(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'prog-patient2@test.com', 'must_change_password' => false]);

        foreach ([96, 98] as $i => $value) {
            HomeMonitoringReading::query()->create([
                'pics_case_id' => $case->id, 'reading_type' => 'spo2', 'value' => $value, 'unit' => '%',
                'measured_at' => now()->addDays($i), 'recorded_by_type' => Patient::class, 'recorded_by_id' => $patient->id,
            ]);
        }

        $this->actingAs($patient, 'patient')
            ->get('/portal/progreso')
            ->assertOk()
            ->assertSee('Saturación de oxígeno')
            ->assertSee('chart.umd.min.js', false)
            ->assertDontSee('Todavía no tienes suficientes registros');
    }

    public function test_shows_wellbeing_chart_for_patient_with_two_or_more_self_reports(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'prog-patient3@test.com', 'must_change_password' => false]);

        foreach (['7d' => 6, '30d' => 3] as $checkpoint => $score) {
            PicsFollowup::query()->create([
                'pics_case_id' => $case->id, 'checkpoint' => $checkpoint, 'respondent_type' => 'paciente',
                'submitted_by_type' => Patient::class, 'submitted_by_id' => $patient->id,
                'followed_up_at' => now(), 'contact_achieved' => true, 'hads_ansiedad' => $score, 'phq9_score' => $score,
            ]);
        }

        $this->actingAs($patient, 'patient')
            ->get('/portal/progreso')
            ->assertOk()
            ->assertSee('Bienestar')
            ->assertSee('Ansiedad (HADS-A)');
    }

    public function test_shows_a_warning_when_there_is_no_active_case(): void
    {
        $patient = Patient::query()->create(['identification' => uniqid('PROG-'), 'full_name' => 'Sin caso']);
        $patient->update(['email' => 'prog-nocase@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal/progreso')
            ->assertOk()
            ->assertSee('Todavía no tienes un programa de seguimiento activo');
    }
}
