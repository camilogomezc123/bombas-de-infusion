<?php

namespace Tests\Feature\Pics;

use App\Models\ClinicalProgram;
use App\Models\HomeMonitoringReading;
use App\Models\MedicationReconciliation;
use App\Models\Patient;
use App\Models\PicsAgendaItem;
use App\Models\PicsCase;
use App\Models\RecoveryGoal;
use App\Models\SupportRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAppointmentSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('SUM-'), 'full_name' => 'Resumen cita']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_summary_shows_active_medications_goals_readings_and_pending_requests(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'sum-patient@test.com', 'must_change_password' => false]);

        $reconciliation = MedicationReconciliation::query()->create(['pics_case_id' => $case->id]);
        $reconciliation->items()->create([
            'medication_name' => 'Enalapril', 'dose' => '10mg', 'route' => 'oral', 'frequency' => 'Cada 12 horas', 'status' => 'continua',
        ]);
        $reconciliation->items()->create([
            'medication_name' => 'Suspendido', 'dose' => '5mg', 'status' => 'suspendida',
        ]);

        RecoveryGoal::query()->create([
            'pics_case_id' => $case->id, 'domain' => 'movilidad', 'description' => 'Caminar 100 metros sin ayuda', 'status' => 'active',
        ]);

        HomeMonitoringReading::query()->create([
            'pics_case_id' => $case->id, 'reading_type' => 'spo2', 'value' => 97, 'unit' => '%',
            'measured_at' => now(), 'recorded_by_type' => Patient::class, 'recorded_by_id' => $patient->id,
        ]);

        SupportRequest::query()->create([
            'pics_case_id' => $case->id, 'type' => 'duda_medicamento', 'description' => '¿El enalapril con el desayuno?',
            'priority' => 'media', 'status' => 'nueva', 'created_by_type' => Patient::class, 'created_by_id' => $patient->id,
        ]);

        PicsAgendaItem::query()->create([
            'pics_case_id' => $case->id, 'type' => 'cita', 'title' => 'Control fisiatría', 'status' => 'pendiente',
            'scheduled_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($patient, 'patient')->get('/portal/resumen-cita');

        $response->assertOk()
            ->assertSee('Enalapril')
            ->assertDontSee('Suspendido')
            ->assertSee('Caminar 100 metros sin ayuda')
            ->assertSee('Saturación de oxígeno')
            ->assertSee('¿El enalapril con el desayuno?')
            ->assertSee('Control fisiatría')
            ->assertSee('Imprimir');
    }

    public function test_summary_hides_answered_support_requests(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'sum-patient2@test.com', 'must_change_password' => false]);

        SupportRequest::query()->create([
            'pics_case_id' => $case->id, 'type' => 'dificultad', 'description' => 'Ya respondida',
            'priority' => 'baja', 'status' => 'respondida', 'response_text' => 'Ya se resolvió',
            'created_by_type' => Patient::class, 'created_by_id' => $patient->id,
        ]);

        $this->actingAs($patient, 'patient')
            ->get('/portal/resumen-cita')
            ->assertOk()
            ->assertDontSee('Ya respondida');
    }

    public function test_summary_shows_a_warning_when_there_is_no_active_case(): void
    {
        $patient = Patient::query()->create(['identification' => uniqid('SUM-'), 'full_name' => 'Sin caso']);
        $patient->update(['email' => 'sum-nocase@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal/resumen-cita')
            ->assertOk()
            ->assertSee('Todavía no tienes un programa de seguimiento activo');
    }
}
