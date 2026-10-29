<?php

namespace Tests\Feature\Pics;

use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalNavigationRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('NAV-'), 'full_name' => 'Navegación']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_settings_dropdown_groups_easy_mode_push_install_and_logout(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'nav-patient@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertSee('⚙️ Ajustes')
            ->assertSee('id="pushToggleBtn"', false)
            ->assertSee('id="installAppBtn"', false)
            ->assertSee('🚪 Salir');
    }

    public function test_module_launcher_is_available_from_a_non_home_page(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'nav-patient2@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal/diario')
            ->assertOk()
            ->assertSee('🗺️ Módulos')
            ->assertSee('id="navModulesOffcanvas"', false)
            ->assertSee('Mi progreso')
            ->assertSee('Resumen para tu cita');
    }
}
