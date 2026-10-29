<?php

namespace Tests\Feature\Sepsis;

use App\Enums\ProgramRole;
use App\Enums\UserRole;
use App\Models\ClinicalProgram;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepsisProgramAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_without_membership_cannot_access_sepsis_panel(): void
    {
        $user = User::factory()->create(['role' => UserRole::Viewer]);
        $panel = app('filament')->getPanel('sepsis');

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_active_member_can_access_sepsis_panel(): void
    {
        $user = User::factory()->create(['role' => UserRole::Viewer]);
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();

        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $user->id,
            'role' => ProgramRole::Viewer,
            'is_active' => true,
        ]);

        $this->assertTrue($user->canAccessPanel(app('filament')->getPanel('sepsis')));
    }

    public function test_global_leader_without_membership_cannot_manage_sepsis_cases(): void
    {
        $user = User::factory()->create(['role' => UserRole::Leader]);

        $this->assertFalse($user->canManageSepsisCases());
    }

    public function test_program_leader_can_manage_sepsis_cases(): void
    {
        $user = User::factory()->create(['role' => UserRole::Viewer]);
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();

        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $user->id,
            'role' => ProgramRole::Leader,
            'is_active' => true,
        ]);

        $this->assertTrue($user->canManageSepsisCases());
    }
}
