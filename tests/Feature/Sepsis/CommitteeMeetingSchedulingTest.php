<?php

namespace Tests\Feature\Sepsis;

use App\Enums\ProgramRole;
use App\Models\ClinicalProgram;
use App\Models\CommitteeMeeting;
use App\Models\CommitteeMember;
use App\Models\ProgramCommittee;
use App\Models\ProgramMember;
use App\Models\User;
use App\Notifications\CommitteeMeetingScheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommitteeMeetingSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_attendees_resolve_email_from_member_or_linked_user(): void
    {
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        $committee = ProgramCommittee::query()->create(['clinical_program_id' => $program->id, 'name' => 'Comité de prueba']);
        $user = User::factory()->create(['email' => 'usuario@example.test']);

        $withOwnEmail = CommitteeMember::query()->create([
            'program_committee_id' => $committee->id, 'display_name' => 'Externo', 'email' => 'externo@example.test', 'is_active' => true,
        ]);
        $withUserEmail = CommitteeMember::query()->create([
            'program_committee_id' => $committee->id, 'user_id' => $user->id, 'display_name' => $user->name, 'is_active' => true,
        ]);
        $withoutEmail = CommitteeMember::query()->create([
            'program_committee_id' => $committee->id, 'display_name' => 'Sin correo', 'is_active' => true,
        ]);

        $this->assertSame('externo@example.test', $withOwnEmail->notificationEmail());
        $this->assertSame('usuario@example.test', $withUserEmail->notificationEmail());
        $this->assertNull($withoutEmail->notificationEmail());
    }

    public function test_scheduling_a_meeting_notifies_only_attendees_with_a_resolvable_email(): void
    {
        Notification::fake();

        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        $committee = ProgramCommittee::query()->create(['clinical_program_id' => $program->id, 'name' => 'Comité de prueba']);
        $withEmail = CommitteeMember::query()->create([
            'program_committee_id' => $committee->id, 'display_name' => 'Con correo', 'email' => 'con.correo@example.test', 'is_active' => true,
        ]);
        $withoutEmail = CommitteeMember::query()->create([
            'program_committee_id' => $committee->id, 'display_name' => 'Sin correo', 'is_active' => true,
        ]);
        $meeting = CommitteeMeeting::query()->create([
            'program_committee_id' => $committee->id,
            'title' => 'Sesión ordinaria',
            'scheduled_at' => now()->addWeek(),
            'status' => 'scheduled',
        ]);
        $meeting->attendees()->sync([$withEmail->id, $withoutEmail->id]);

        $sent = 0;
        foreach ($meeting->attendees as $member) {
            if (! $email = $member->notificationEmail()) {
                continue;
            }
            Notification::route('mail', $email)->notify(new CommitteeMeetingScheduledNotification($meeting, $member->display_name));
            $sent++;
        }

        $this->assertSame(1, $sent);
        Notification::assertSentOnDemand(
            CommitteeMeetingScheduledNotification::class,
            fn (CommitteeMeetingScheduledNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'con.correo@example.test',
        );
    }

    public function test_meeting_minutes_pdf_can_be_stored_and_downloaded(): void
    {
        Storage::fake('local');

        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        $committee = ProgramCommittee::query()->create(['clinical_program_id' => $program->id, 'name' => 'Comité de prueba']);
        $meeting = CommitteeMeeting::query()->create([
            'program_committee_id' => $committee->id,
            'title' => 'Sesión con acta',
            'scheduled_at' => now(),
            'status' => 'completed',
        ]);

        $path = UploadedFile::fake()->create('acta.pdf', 10, 'application/pdf')
            ->store('committee-minutes', 'local');
        $meeting->update(['minutes_file_path' => $path]);

        Storage::disk('local')->assertExists($path);
        $this->assertTrue($meeting->fresh()->minutes_file_path === $path);
    }

    public function test_meeting_management_requires_governance_permission(): void
    {
        $viewer = User::factory()->create();
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id, 'user_id' => $viewer->id, 'role' => ProgramRole::Viewer, 'is_active' => true,
        ]);
        $committee = ProgramCommittee::query()->create(['clinical_program_id' => $program->id, 'name' => 'Comité de prueba']);
        $meeting = CommitteeMeeting::query()->create([
            'program_committee_id' => $committee->id, 'title' => 'Sesión', 'scheduled_at' => now(), 'status' => 'scheduled',
        ]);

        $this->assertFalse($viewer->can('update', $meeting));
        $this->assertFalse($viewer->can('create', CommitteeMeeting::class));
    }
}
