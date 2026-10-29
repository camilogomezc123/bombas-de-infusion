<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\SupportRequestReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SupportRequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('SN-'), 'full_name' => 'Notificación']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    public function test_notifies_the_assigned_auditor_when_a_case_has_one(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $auditor = User::factory()->create();
        $case->update(['assigned_auditor_id' => $auditor->id]);

        $case->supportRequests()->create([
            'type' => 'dificultad', 'description' => 'Necesito ayuda con la movilidad',
            'created_by_type' => Patient::class, 'created_by_id' => $case->patient_id,
        ]);

        Notification::assertSentTo($auditor, SupportRequestReceivedNotification::class);
    }

    public function test_notifies_program_leaders_when_no_auditor_is_assigned(): void
    {
        Notification::fake();

        $case = $this->makeCase();
        $leader = User::factory()->create();
        ProgramMember::query()->create([
            'clinical_program_id' => $case->clinical_program_id, 'user_id' => $leader->id, 'role' => ProgramRole::Leader,
        ]);
        $viewer = User::factory()->create();
        ProgramMember::query()->create([
            'clinical_program_id' => $case->clinical_program_id, 'user_id' => $viewer->id, 'role' => ProgramRole::Viewer,
        ]);

        $case->supportRequests()->create([
            'type' => 'dificultad', 'description' => 'Sin auditor todavía',
            'created_by_type' => Patient::class, 'created_by_id' => $case->patient_id,
        ]);

        Notification::assertSentTo($leader, SupportRequestReceivedNotification::class);
        Notification::assertNotSentTo($viewer, SupportRequestReceivedNotification::class);
    }

    public function test_urgent_priority_produces_a_distinct_title(): void
    {
        $case = $this->makeCase();
        $auditor = User::factory()->create();
        $case->update(['assigned_auditor_id' => $auditor->id]);

        $request = $case->supportRequests()->create([
            'type' => 'dificultad', 'description' => 'Muy urgente', 'priority' => 'alta',
            'created_by_type' => Patient::class, 'created_by_id' => $case->patient_id,
        ]);

        $notification = new SupportRequestReceivedNotification($request->fresh(['case']));
        $data = $notification->toArray($auditor);

        $this->assertStringContainsString('Urgente', $data['title']);
    }
}
