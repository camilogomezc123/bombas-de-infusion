<?php

namespace Tests\Feature\Pics;

use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalInstallPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_includes_the_install_app_button_and_script(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('INSTALL-'), 'full_name' => 'Instalar app']);
        $patient->update(['email' => 'install-patient@test.com', 'must_change_password' => false]);
        PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertSee('id="installAppBtn"', false)
            ->assertSee('portal-install.js');
    }
}
