<?php

namespace Tests\Feature\Posuci;

use App\Livewire\Portal\PassportComponent;
use App\Livewire\Portal\SupportRequestComponent;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\RecoveryPassportItem;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PassportAndSupportRequestComponentTest extends TestCase
{
    use RefreshDatabase;

    private function makeCaseWithCaregiver(string $identification = 'P-1'): array
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => $identification, 'full_name' => 'Paciente '.$identification]);
        $case = PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
        $staff = User::factory()->create();
        $caregiver = Caregiver::query()->create([
            'name' => 'Cuidador '.$identification,
            'email' => "cuidador-{$identification}@test.com",
            'password' => Hash::make('secret123'),
        ]);
        CaregiverAuthorization::query()->create([
            'pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id, 'can_write_diary' => true,
            'authorized_by' => $staff->id, 'authorized_at' => now(),
        ]);

        return compact('case', 'patient', 'caregiver', 'staff');
    }

    public function test_caregiver_fills_the_passport_and_adds_a_meaningful_goal_item(): void
    {
        ['case' => $case, 'caregiver' => $caregiver] = $this->makeCaseWithCaregiver();

        $this->actingAs($caregiver, 'caregiver');
        Livewire::test(PassportComponent::class)
            ->set('mobility_before', 'Caminaba sin ayuda, subía escaleras')
            ->set('current_situation', 'Necesita andador')
            ->call('save')
            ->set('new_item_type', RecoveryPassportItem::TYPE_ASPIRATION)
            ->set('new_item_description', 'Quiero volver a cocinar')
            ->call('addItem');

        $passport = $case->recoveryPassport()->firstOrFail();
        $this->assertSame('Caminaba sin ayuda, subía escaleras', $passport->mobility_before);
        $this->assertSame(Caregiver::class, $passport->reported_by_type);
        $this->assertFalse($passport->is_confirmed);
        $this->assertSame('Quiero volver a cocinar', $passport->items()->first()->description);
    }

    public function test_caregiver_of_another_case_cannot_see_or_edit_a_different_passport(): void
    {
        $dataA = $this->makeCaseWithCaregiver('PA-1');
        $dataB = $this->makeCaseWithCaregiver('PB-1');
        $dataA['case']->recoveryPassport()->create(['mobility_before' => 'Dato privado de A']);

        $this->actingAs($dataB['caregiver'], 'caregiver');
        Livewire::test(PassportComponent::class)->assertDontSee('Dato privado de A');
    }

    public function test_patient_reports_a_difficulty_professional_responds_and_patient_sees_the_response(): void
    {
        ['case' => $case, 'patient' => $patient] = $this->makeCaseWithCaregiver();

        // El paciente reporta.
        $this->actingAs($patient, 'patient');
        Livewire::test(SupportRequestComponent::class)
            ->set('description', 'Me duele mucho la pierna al caminar')
            ->set('priority', 'alta')
            ->call('save');

        $request = SupportRequest::query()->where('pics_case_id', $case->id)->firstOrFail();
        $this->assertSame(Patient::class, $request->created_by_type);
        $this->assertSame('nueva', $request->status);
        $this->assertFalse($request->isAnswered());

        // El profesional responde (fuera del portal, en el panel).
        $leader = User::factory()->create();
        $request->update([
            'response_text' => 'Vamos a ajustar tu manejo del dolor en la próxima cita.',
            'responded_by' => $leader->id,
            'responded_at' => now(),
            'status' => 'respondida',
        ]);

        // El paciente consulta la respuesta.
        $this->actingAs($patient, 'patient');
        Livewire::test(SupportRequestComponent::class)
            ->assertSee('Vamos a ajustar tu manejo del dolor')
            ->assertSee('Respondida');
    }

    public function test_caregiver_of_another_case_cannot_see_a_different_support_request(): void
    {
        $dataA = $this->makeCaseWithCaregiver('SA-1');
        $dataB = $this->makeCaseWithCaregiver('SB-1');
        $dataA['case']->supportRequests()->create([
            'type' => 'dificultad', 'description' => 'Solicitud privada de A', 'created_by_type' => Patient::class,
            'created_by_id' => $dataA['patient']->id,
        ]);

        $this->actingAs($dataB['caregiver'], 'caregiver');
        Livewire::test(SupportRequestComponent::class)->assertDontSee('Solicitud privada de A');
    }
}
