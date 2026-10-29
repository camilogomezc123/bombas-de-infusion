<?php

namespace Tests\Feature\Posuci;

use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class PortalTourTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('PT-'), 'full_name' => 'Tour']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_tour_shows_for_a_new_patient_and_can_be_dismissed(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'tour-patient@test.com', 'must_change_password' => false]);

        $this->assertFalse($patient->fresh()->has_seen_portal_tour);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertSee('portalTourModal');

        $this->actingAs($patient, 'patient')
            ->post('/portal/tour-visto')
            ->assertRedirect(route('portal.home'));

        $this->assertTrue($patient->fresh()->has_seen_portal_tour);
    }

    public function test_tour_does_not_show_again_once_dismissed(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'tour-patient2@test.com', 'must_change_password' => false, 'has_seen_portal_tour' => true]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertDontSee('portalTourModal');
    }
}
