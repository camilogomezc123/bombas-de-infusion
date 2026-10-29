<?php

namespace Tests\Feature\Sepsis;

use App\Enums\ProgramRole;
use App\Models\ClinicalProgram;
use App\Models\ProgramMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepsisDirectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_membership_is_denied_on_direct_panel_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/sepsis')->assertForbidden();
    }

    public function test_program_viewer_can_open_panel_but_cannot_create_cases(): void
    {
        $user = User::factory()->create();
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $user->id,
            'role' => ProgramRole::Viewer,
        ]);

        $this->actingAs($user)->get('/sepsis')->assertOk();
        $this->actingAs($user)->get('/sepsis/sepsis-cases/create')->assertForbidden();
    }
}
