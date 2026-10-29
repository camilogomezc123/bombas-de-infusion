<?php

namespace Tests\Feature;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Models\AcvCase;
use App\Models\CaseComment;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\CaseConversationNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CaseNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_acv_panel_exposes_database_notifications_in_the_header(): void
    {
        $this->assertTrue(Filament::getPanel('acv')->hasDatabaseNotifications());
    }

    public function test_assignment_notification_uses_filament_format_and_links_to_the_case(): void
    {
        $auditor = User::factory()->create(['role' => UserRole::Auditor]);
        $case = $this->createCase($auditor);
        $data = (new CaseAssignedNotification($case))->toArray($auditor);

        $this->assertSame('filament', $data['format']);
        $this->assertSame("Caso {$case->case_number} asignado", $data['title']);
        $this->assertStringContainsString("/acv/acv-cases/{$case->id}/edit", $data['actions'][0]['url']);
    }

    public function test_auditor_comment_notifies_leaders_and_administrators_only(): void
    {
        Notification::fake();

        $auditor = User::factory()->create(['role' => UserRole::Auditor]);
        $otherAuditor = User::factory()->create(['role' => UserRole::Auditor]);
        $leader = User::factory()->create(['role' => UserRole::Leader]);
        $administrator = User::factory()->create(['role' => UserRole::Administrator]);
        $case = $this->createCase($auditor);

        CaseComment::query()->create([
            'acv_case_id' => $case->id,
            'user_id' => $auditor->id,
            'body' => '¿Se confirma la fecha de egreso?',
        ]);

        Notification::assertSentTo([$leader, $administrator], CaseConversationNotification::class);
        Notification::assertNotSentTo($otherAuditor, CaseConversationNotification::class);
    }

    public function test_leader_comment_notifies_the_assigned_auditor(): void
    {
        Notification::fake();

        $auditor = User::factory()->create(['role' => UserRole::Auditor]);
        $leader = User::factory()->create(['role' => UserRole::Leader]);
        $case = $this->createCase($auditor);

        CaseComment::query()->create([
            'acv_case_id' => $case->id,
            'user_id' => $leader->id,
            'body' => 'La fecha fue confirmada.',
        ]);

        Notification::assertSentTo($auditor, CaseConversationNotification::class);
    }

    public function test_auditor_observation_update_notifies_the_management_team(): void
    {
        Notification::fake();

        $auditor = User::factory()->create(['role' => UserRole::Auditor]);
        $leader = User::factory()->create(['role' => UserRole::Leader]);
        $administrator = User::factory()->create(['role' => UserRole::Administrator]);
        $case = $this->createCase($auditor);

        $this->actingAs($auditor);
        $case->update([
            'clinical_data' => ['observations' => 'Revisar la hora de imagen registrada.'],
        ]);

        Notification::assertSentTo([$leader, $administrator], CaseConversationNotification::class);
    }

    private function createCase(User $auditor): AcvCase
    {
        $patient = Patient::query()->create([
            'identification' => fake()->unique()->numerify('########'),
            'full_name' => 'Paciente notificaciones',
        ]);

        return AcvCase::withoutEvents(fn () => AcvCase::query()->create([
            'patient_id' => $patient->id,
            'assigned_auditor_id' => $auditor->id,
            'case_number' => 'NOT-'.fake()->unique()->numberBetween(1, 99999),
            'case_sequence' => fake()->unique()->numberBetween(1, 99999),
            'status' => CaseStatus::Assigned,
            'is_cancelled' => false,
        ]));
    }
}
