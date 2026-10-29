<?php

namespace Tests\Feature;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Filament\Acv\Resources\AcvCases\AcvCaseResource;
use App\Filament\Pages\Auth\Login;
use App\Models\AcvCase;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_admin_login_when_the_host_is_not_the_portal_domain(): void
    {
        config(['app.portal_url' => 'http://portal-solo-existe-en-otro-dominio.test']);

        $this->get('/')->assertRedirect('/admin/login');
    }

    public function test_root_redirects_to_portal_login_when_the_host_is_the_portal_domain(): void
    {
        config(['app.portal_url' => 'http://localhost']);

        $this->get('/')->assertRedirect(route('portal.login'));
    }

    public function test_login_page_renders_in_spanish(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Bienvenido a ÁGORA')
            ->assertSee('Analítica y Gestión Operacional');
    }

    public function test_user_can_login_with_username_or_email(): void
    {
        $user = User::factory()->create([
            'username' => 'AUD001',
            'email' => 'auditor@example.com',
            'password' => 'password',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'aud001',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
        auth()->logout();

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'auditor@example.com',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_administrator_can_open_users_and_cases(): void
    {
        $administrator = User::factory()->create([
            'role' => UserRole::Administrator,
            'email' => 'alexandertorresviveros@gmail.com',
        ]);

        $this->actingAs($administrator)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Buscar por nombre, usuario o correo');

        $this->actingAs($administrator)
            ->get('/acv/acv-cases')
            ->assertOk();
    }

    public function test_auditor_cannot_open_user_management(): void
    {
        $auditor = User::factory()->create([
            'role' => UserRole::Auditor,
        ]);

        $this->actingAs($auditor)
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($auditor)
            ->get('/admin/profile')
            ->assertOk();
    }

    public function test_active_acv_leader_can_manage_users(): void
    {
        $leader = User::factory()->create([
            'name' => 'Laura Galarza',
            'role' => UserRole::Leader,
            'email' => 'programa.acv@clinicadeoccidente.com',
            'is_active' => true,
        ]);

        $this->actingAs($leader)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_other_leaders_and_inactive_former_leader_cannot_manage_users(): void
    {
        $otherLeader = User::factory()->create([
            'role' => UserRole::Leader,
        ]);
        $formerLeader = User::factory()->create([
            'name' => 'María José Carvajal',
            'role' => UserRole::Leader,
            'email' => 'former.leader@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($otherLeader)
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($formerLeader)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_dashboard_and_case_detail_render(): void
    {
        $administrator = User::factory()->create([
            'role' => UserRole::Administrator,
            'email' => 'alexandertorresviveros@gmail.com',
        ]);
        $patient = Patient::query()->create([
            'identification' => '123456',
            'full_name' => 'Paciente de prueba',
        ]);
        $case = AcvCase::withoutEvents(fn () => AcvCase::query()->create([
            'patient_id' => $patient->id,
            'case_number' => 'S1',
            'case_sequence' => 1,
            'status' => CaseStatus::Imported,
        ]));

        $this->actingAs($administrator)
            ->get('/admin')
            ->assertOk();

        $this->actingAs($administrator)
            ->get("/acv/acv-cases/{$case->id}")
            ->assertOk()
            ->assertSee('Paciente de prueba');

        $this->actingAs($administrator)
            ->get('/acv')
            ->assertOk()
            ->assertSee('Indicadores ACV');
    }

    public function test_auditor_only_edits_assigned_cases(): void
    {
        $auditor = User::factory()->create(['role' => UserRole::Auditor]);
        $otherAuditor = User::factory()->create(['role' => UserRole::Auditor]);
        $patient = Patient::query()->create([
            'identification' => '654321',
            'full_name' => 'Otro paciente',
        ]);
        $case = AcvCase::withoutEvents(fn () => AcvCase::query()->create([
            'patient_id' => $patient->id,
            'assigned_auditor_id' => $otherAuditor->id,
            'case_number' => 'S2',
            'case_sequence' => 2,
            'status' => CaseStatus::Assigned,
        ]));

        $this->actingAs($auditor);
        $this->assertFalse(AcvCaseResource::canEdit($case));

        $case->assigned_auditor_id = $auditor->id;
        $this->assertTrue(AcvCaseResource::canEdit($case));
    }

    public function test_auditor_only_sees_assigned_pending_analysis_cases(): void
    {
        $auditor = User::factory()->create(['role' => UserRole::Auditor]);
        $otherAuditor = User::factory()->create(['role' => UserRole::Auditor]);
        $patient = Patient::query()->create([
            'identification' => '777777',
            'full_name' => 'Paciente pendientes',
        ]);

        $assignedCases = collect(range(1, 3))->map(fn (int $sequence): AcvCase => $this->createPendingCase(
            $patient,
            $sequence,
            $auditor,
        ));

        $otherCases = collect(range(4, 10))->map(fn (int $sequence): AcvCase => $this->createPendingCase(
            $patient,
            $sequence,
            $otherAuditor,
        ));

        $response = $this->actingAs($auditor)->get('/acv/egresados');

        $response
            ->assertOk()
            ->assertSee('Pendientes de análisis');

        $assignedCases->each(fn (AcvCase $case) => $response->assertSee($case->case_number));
        $otherCases->each(fn (AcvCase $case) => $response->assertDontSee($case->case_number));

        $this->actingAs($auditor)
            ->get("/acv/acv-cases/{$otherCases->first()->id}")
            ->assertNotFound();
    }

    public function test_viewer_cannot_open_pending_analysis_cases(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($viewer)
            ->get('/acv/egresados')
            ->assertForbidden();
    }

    private function createPendingCase(Patient $patient, int $sequence, User $auditor): AcvCase
    {
        return AcvCase::withoutEvents(fn () => AcvCase::query()->create([
            'patient_id' => $patient->id,
            'assigned_auditor_id' => $auditor->id,
            'case_number' => 'PEND-'.$sequence,
            'case_sequence' => 100 + $sequence,
            'status' => CaseStatus::Assigned,
            'discharged_at' => now(),
        ]));
    }
}
