<?php

namespace Tests\Feature\Pics;

use App\Models\ClinicalProgram;
use App\Models\HomeMonitoringReading;
use App\Models\Patient;
use App\Models\PicsAgendaItem;
use App\Models\PicsCase;
use App\Models\PicsFollowup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalDailyRitualTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('DR-'), 'full_name' => 'Ritual diario']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_home_shows_todays_ritual_with_pending_items(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'dr-patient@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertSee('Tu día')
            ->assertSee('¿Cómo amaneciste?')
            ->assertSee('Monitoreo en casa')
            ->assertSee('Escribe en tu diario')
            ->assertSee('Toca para hacerlo ahora');
    }

    public function test_completed_items_show_as_done_and_todays_appointment_appears_in_the_agenda(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'dr-patient2@test.com', 'must_change_password' => false]);

        HomeMonitoringReading::query()->create([
            'pics_case_id' => $case->id, 'reading_type' => 'saturacion', 'value' => 97, 'unit' => '%',
            'measured_at' => now(), 'recorded_by_type' => Patient::class, 'recorded_by_id' => $patient->id,
        ]);

        $case->followups()->create([
            'checkpoint' => '7d', 'respondent_type' => 'paciente', 'contact_achieved' => true,
            'followed_up_at' => now(), 'submitted_by_type' => Patient::class, 'submitted_by_id' => $patient->id,
        ]);

        PicsAgendaItem::query()->create([
            'pics_case_id' => $case->id, 'type' => 'cita', 'title' => 'Control fisiatría', 'status' => 'pendiente',
            'scheduled_at' => now()->setTime(15, 0),
        ]);

        $response = $this->actingAs($patient, 'patient')->get('/portal');

        $response->assertOk()
            ->assertSee('¿Cómo amaneciste?')
            ->assertSee('Monitoreo en casa')
            ->assertSee('Cita: Control fisiatría')
            ->assertSee('15:00');
    }

    public function test_ritual_is_not_shown_when_there_is_no_active_case(): void
    {
        $patient = Patient::query()->create(['identification' => uniqid('DR-'), 'full_name' => 'Sin caso']);
        $patient->update(['email' => 'dr-nocase@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertDontSee('Tu día');
    }
}
