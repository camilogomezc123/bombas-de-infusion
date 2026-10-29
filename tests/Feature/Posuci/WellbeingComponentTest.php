<?php

namespace Tests\Feature\Posuci;

use App\Livewire\Portal\WellbeingComponent;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\PicsFollowup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WellbeingComponentTest extends TestCase
{
    use RefreshDatabase;

    private function makeCaseAtDay(int $daysAgo): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create([
            'identification' => uniqid('W-'), 'full_name' => 'Bienestar',
            'email' => uniqid().'@test.com', 'password' => bcrypt('secret'),
        ]);

        return PicsCase::query()->create([
            'clinical_program_id' => $program->id,
            'patient_id' => $patient->id,
            'enrollment_at' => now()->subDays($daysAgo),
        ]);
    }

    public function test_no_checkpoint_is_offered_outside_the_follow_up_windows(): void
    {
        $case = $this->makeCaseAtDay(17); // entre 7d (5-10) y 30d (25-40): sin ventana activa
        $this->actingAs($case->patient, 'patient');

        Livewire::test(WellbeingComponent::class)
            ->assertSet('checkpoint', null)
            ->assertSee('Por ahora no tienes un cuestionario pendiente');
    }

    public function test_patient_submits_self_report_instruments_and_it_lands_pending_confirmation(): void
    {
        $case = $this->makeCaseAtDay(30); // ventana 30d (25-40)
        $this->actingAs($case->patient, 'patient');

        Livewire::test(WellbeingComponent::class)
            ->assertSet('checkpoint', '30d')
            ->set('hads', [1, 1, 1, 1, 1, 1, 1]) // 7
            ->set('phq9', [1, 1, 1, 1, 1, 1, 1, 1, 1]) // 9
            ->set('pcptsd', [true, true, false, false, false]) // 2
            ->set('fatigueScore', 5)
            ->set('painRest', 2)
            ->set('painMovement', 3)
            ->call('save');

        $followup = $case->followups()->where('checkpoint', '30d')->where('respondent_type', 'paciente')->firstOrFail();

        $this->assertSame(7, $followup->hads_ansiedad);
        $this->assertSame(9, $followup->phq9_score);
        $this->assertSame(2, $followup->pcptsd_score);
        $this->assertSame(Patient::class, $followup->submitted_by_type);
        $this->assertSame($case->patient->id, $followup->submitted_by_id);
        $this->assertTrue($followup->isSelfSubmitted());
        $this->assertFalse($followup->isConfirmed());

        // El profesional lo confirma desde el panel.
        $followup->update(['confirmed_by' => \App\Models\User::factory()->create()->id, 'confirmed_at' => now()]);
        $this->assertTrue($followup->fresh()->isConfirmed());
    }

    public function test_ptg_is_only_offered_at_the_later_checkpoints(): void
    {
        $early = $this->makeCaseAtDay(7); // 7d
        $this->actingAs($early->patient, 'patient');
        Livewire::test(WellbeingComponent::class)->assertSet('checkpoint', '7d')->assertViewHas('showPtg', false);

        $late = $this->makeCaseAtDay(90); // 3m
        $this->actingAs($late->patient, 'patient');
        Livewire::test(WellbeingComponent::class)->assertSet('checkpoint', '3m')->assertViewHas('showPtg', true);
    }
}
