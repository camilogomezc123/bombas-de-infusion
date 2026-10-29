<?php

namespace Tests\Feature\Pics;

use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalVoiceInputTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('VOICE-'), 'full_name' => 'Voz en vez de texto']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_diary_form_has_a_microphone_button_for_each_text_field(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'voice-patient@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal/diario')
            ->assertOk()
            ->assertSee('data-target="diary-content"', false)
            ->assertSee('data-target="diary-memory"', false)
            ->assertSee('portal-voice.js');
    }

    public function test_support_request_form_has_a_microphone_button(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'voice-patient2@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal/ayuda')
            ->assertOk()
            ->assertSee('data-target="support-description"', false);
    }
}
