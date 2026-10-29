<?php

namespace Tests\Feature\Pics;

use App\Enums\ProgramRole;
use App\Filament\Pics\Pages\CoordinatedAgenda;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsAgendaItem;
use App\Models\PicsCase;
use App\Models\PicsReferral;
use App\Models\ProgramMember;
use App\Models\User;
use App\Services\PicsAgendaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CoordinatedAgendaTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PicsCase
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('AG-'), 'full_name' => 'Agenda']);

        return PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);
    }

    private function leaderFor(PicsCase $case): User
    {
        $leader = User::factory()->create();
        ProgramMember::query()->create([
            'clinical_program_id' => $case->clinical_program_id, 'user_id' => $leader->id, 'role' => ProgramRole::Leader,
        ]);

        return $leader;
    }

    public function test_service_merges_referrals_and_agenda_items_sorted_by_date(): void
    {
        $case = $this->makeCase();

        PicsReferral::query()->create(['pics_case_id' => $case->id, 'specialty' => 'Neumología', 'status' => 'scheduled', 'scheduled_at' => now()->addDays(3)]);
        PicsAgendaItem::query()->create(['pics_case_id' => $case->id, 'type' => 'tarea', 'title' => 'Llamar a la familia', 'scheduled_at' => now()->addDay(), 'status' => 'pendiente']);
        PicsAgendaItem::query()->create(['pics_case_id' => $case->id, 'type' => 'cita', 'title' => 'Control fisiatría', 'scheduled_at' => now()->addDays(5), 'status' => 'pendiente']);

        $rows = app(PicsAgendaService::class)->upcoming(14);

        $this->assertCount(3, $rows);
        $this->assertSame('Llamar a la familia', $rows[0]['title']);
        $this->assertSame('Control fisiatría', $rows[2]['title']);
    }

    public function test_service_respects_the_days_window(): void
    {
        $case = $this->makeCase();

        PicsAgendaItem::query()->create(['pics_case_id' => $case->id, 'type' => 'tarea', 'title' => 'Dentro de la ventana', 'scheduled_at' => now()->addDays(5), 'status' => 'pendiente']);
        PicsAgendaItem::query()->create(['pics_case_id' => $case->id, 'type' => 'tarea', 'title' => 'Fuera de la ventana', 'scheduled_at' => now()->addDays(30), 'status' => 'pendiente']);

        $rows = app(PicsAgendaService::class)->upcoming(14);

        $this->assertCount(1, $rows);
        $this->assertSame('Dentro de la ventana', $rows[0]['title']);
    }

    public function test_service_excludes_overdue_when_disabled(): void
    {
        $case = $this->makeCase();

        PicsAgendaItem::query()->create(['pics_case_id' => $case->id, 'type' => 'tarea', 'title' => 'Vencida', 'scheduled_at' => now()->subDay(), 'status' => 'pendiente']);

        $withOverdue = app(PicsAgendaService::class)->upcoming(14, true);
        $withoutOverdue = app(PicsAgendaService::class)->upcoming(14, false);

        $this->assertCount(1, $withOverdue);
        $this->assertCount(0, $withoutOverdue);
    }

    public function test_service_excludes_completed_and_cancelled_and_closed(): void
    {
        $case = $this->makeCase();

        PicsAgendaItem::query()->create(['pics_case_id' => $case->id, 'type' => 'tarea', 'title' => 'Completada', 'scheduled_at' => now()->addDay(), 'status' => 'completada']);
        PicsAgendaItem::query()->create(['pics_case_id' => $case->id, 'type' => 'tarea', 'title' => 'Cancelada', 'scheduled_at' => now()->addDay(), 'status' => 'cancelada']);
        PicsReferral::query()->create(['pics_case_id' => $case->id, 'specialty' => 'Nutrición', 'status' => 'completed', 'scheduled_at' => now()->addDay()]);

        $rows = app(PicsAgendaService::class)->upcoming(14);

        $this->assertCount(0, $rows);
    }

    public function test_relation_manager_crud_and_complete_cancel_actions(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);

        $this->actingAs($leader)
            ->get("/pics/pics-cases/{$case->id}")
            ->assertOk()
            ->assertSee('Agenda coordinada');

        $item = PicsAgendaItem::query()->create(['pics_case_id' => $case->id, 'type' => 'tarea', 'title' => 'Tarea de prueba', 'scheduled_at' => now()->addDay(), 'status' => 'pendiente']);
        $item->update(['status' => 'completada', 'completed_by' => $leader->id, 'completed_at' => now()]);

        $this->assertSame('completada', $item->fresh()->status);
    }

    public function test_page_renders_and_groups_by_date(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        PicsAgendaItem::query()->create(['pics_case_id' => $case->id, 'type' => 'tarea', 'title' => 'Agrupable', 'scheduled_at' => now()->addDay(), 'status' => 'pendiente']);

        $this->actingAs($leader);

        Livewire::test(CoordinatedAgenda::class)
            ->assertOk()
            ->assertSee('Agrupable');
    }

    public function test_only_manager_can_cancel_an_agenda_item_from_the_page(): void
    {
        $case = $this->makeCase();
        $leader = $this->leaderFor($case);
        $item = PicsAgendaItem::query()->create(['pics_case_id' => $case->id, 'type' => 'tarea', 'title' => 'Cancelable', 'scheduled_at' => now()->addDay(), 'status' => 'pendiente']);

        $this->actingAs($leader);

        Livewire::test(CoordinatedAgenda::class)
            ->call('cancelAgendaItem', $item->id);

        $this->assertSame('cancelada', $item->fresh()->status);
    }
}
