<?php

namespace Tests\Feature\Posuci;

use App\Models\Caregiver;
use App\Models\Patient;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PortalPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Estas pruebas envían formularios reales (no Livewire) para probar el
        // controlador de contraseñas tal cual lo recibe el navegador.
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_patient_can_reset_password_via_the_patients_broker(): void
    {
        $patient = Patient::query()->create([
            'identification' => 'PW-1', 'full_name' => 'Reset Patient', 'email' => 'reset-patient@test.com',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::broker('patients')->createToken($patient);

        $response = $this->post('/portal/restablecer-password', [
            'token' => $token,
            'guard' => 'patient',
            'email' => $patient->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('portal.login'));
        $this->assertTrue(Hash::check('new-password-123', $patient->fresh()->password));
        $this->assertFalse($patient->fresh()->must_change_password);
    }

    public function test_caregiver_can_reset_password_via_the_caregivers_broker(): void
    {
        $caregiver = Caregiver::query()->create([
            'name' => 'Reset Caregiver', 'email' => 'reset-caregiver@test.com', 'password' => Hash::make('old-password'),
        ]);

        $token = Password::broker('caregivers')->createToken($caregiver);

        $response = $this->post('/portal/restablecer-password', [
            'token' => $token,
            'guard' => 'caregiver',
            'email' => $caregiver->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('portal.login'));
        $this->assertTrue(Hash::check('new-password-123', $caregiver->fresh()->password));
    }

    public function test_an_invalid_token_does_not_reset_the_password(): void
    {
        $patient = Patient::query()->create([
            'identification' => 'PW-2', 'full_name' => 'Bad Token', 'email' => 'bad-token@test.com',
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->post('/portal/restablecer-password', [
            'token' => 'not-a-real-token',
            'guard' => 'patient',
            'email' => $patient->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password', $patient->fresh()->password));
    }

    public function test_must_change_password_blocks_access_until_changed(): void
    {
        $patient = Patient::query()->create([
            'identification' => 'PW-3', 'full_name' => 'Must Change', 'email' => 'must-change@test.com',
            'password' => Hash::make('temp-password'), 'must_change_password' => true,
        ]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertRedirect(route('portal.password.force-change'));

        $this->actingAs($patient, 'patient')
            ->post('/portal/cambiar-contrasena', [
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertRedirect(route('portal.home'));

        $patient->refresh();
        $this->assertFalse($patient->must_change_password);
        $this->assertTrue(Hash::check('brand-new-password', $patient->password));

        $this->actingAs($patient, 'patient')->get('/portal')->assertOk();
    }
}
