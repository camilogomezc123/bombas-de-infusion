<?php

namespace Tests\Feature\Pics;

use App\Models\ClinicalProgram;
use App\Models\DiaryEntry;
use App\Models\Patient;
use App\Models\PersonalReminder;
use App\Models\PicsCase;
use App\Models\RecoveryPassport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalFirstStepsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('STEP-'), 'full_name' => 'Paciente de prueba']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_shows_the_checklist_for_a_brand_new_patient(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'step-patient@test.com', 'must_change_password' => false]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertSee('Primeros pasos')
            ->assertSee('Completa tu "Antes y ahora"')
            ->assertSee('Activa las notificaciones');
    }

    public function test_hides_the_checklist_once_every_step_is_done(): void
    {
        $case = $this->makeCase();
        $patient = $case->patient;
        $patient->update(['email' => 'step-patient2@test.com', 'must_change_password' => false]);

        RecoveryPassport::query()->create([
            'pics_case_id' => $case->id, 'reported_by_type' => Patient::class, 'reported_by_id' => $patient->id, 'reported_at' => now(),
        ]);
        DiaryEntry::query()->create([
            'pics_case_id' => $case->id, 'content' => 'Hoy estuvo bien.', 'entry_date' => today(),
            'authorable_type' => Patient::class, 'authorable_id' => $patient->id,
        ]);
        PersonalReminder::query()->create([
            'pics_case_id' => $case->id, 'title' => 'Tomar agua', 'remind_at' => now()->addDay(),
            'created_by_type' => Patient::class, 'created_by_id' => $patient->id,
        ]);
        $patient->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/'.uniqid(), 'public_key' => 'k', 'auth_token' => 'a',
        ]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertDontSee('🚀 Primeros pasos');
    }
}
