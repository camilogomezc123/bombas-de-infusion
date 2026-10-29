<?php

namespace Tests\Feature\Posuci;

use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class PortalEasyModeTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('EM-'), 'full_name' => 'Modo fácil']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_patient_can_toggle_easy_mode_on_and_off(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'easy-patient@test.com', 'must_change_password' => false]);

        $this->assertFalse($patient->fresh()->portal_easy_mode);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertDontSee('easy-mode', false);

        $this->actingAs($patient, 'patient')->post('/portal/modo-facil')->assertRedirect();
        $patient->refresh();
        $this->assertTrue($patient->portal_easy_mode);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertSee('class="game-mode easy-mode"', false);

        $this->actingAs($patient, 'patient')->post('/portal/modo-facil')->assertRedirect();
        $this->assertFalse($patient->fresh()->portal_easy_mode);
    }
}
