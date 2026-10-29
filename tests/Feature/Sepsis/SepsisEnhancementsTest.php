<?php

namespace Tests\Feature\Sepsis;

use App\Enums\CaseStatus;
use App\Enums\ProgramRole;
use App\Filament\Sepsis\Pages\ClinicalPathway;
use App\Filament\Sepsis\Pages\MonthlyReport;
use App\Filament\Sepsis\Resources\Sites\SiteResource;
use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\ProgramMember;
use App\Models\SepsisCase;
use App\Models\Site;
use App\Models\User;
use App\Services\SepsisExecutiveSummaryService;
use App\Services\SepsisIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepsisEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private function leaderUser(): User
    {
        $user = User::factory()->create();
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->firstOrFail();
        ProgramMember::query()->create([
            'clinical_program_id' => $program->id,
            'user_id' => $user->id,
            'role' => ProgramRole::Leader,
            'is_active' => true,
        ]);

        return $user;
    }

    public function test_active_case_with_site_is_available_to_incremental_indicators(): void
    {
        $site = Site::query()->create(['name' => 'Sede de prueba', 'code' => 'SEDE-TEST']);
        $patient = Patient::query()->create(['identification' => 'ENH-1', 'full_name' => 'Paciente Sede']);

        $before = app(SepsisIndicatorService::class)->summary('2026');

        $case = SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'site_id' => $site->id,
            'status' => CaseStatus::Hospitalized,
            'origin_service' => 'Urgencias',
            'acquisition_type' => 'community',
            'special_population' => ['oncologico', 'adulto_mayor'],
        ]);

        $after = app(SepsisIndicatorService::class)->summary('2026');

        $this->assertSame($site->id, $case->fresh()->site_id);
        $this->assertSame(['oncologico', 'adulto_mayor'], $case->fresh()->special_population);
        $this->assertNull($before);
        $this->assertSame(1, $after['total']);
        $this->assertSame(0, $after['activated_total']);
        $this->assertNull($after['bundle_pct']);
    }

    public function test_executive_summary_distributions_count_special_population_per_entry(): void
    {
        $patient = Patient::query()->create(['identification' => 'ENH-2', 'full_name' => 'Paciente Distribución']);

        SepsisCase::query()->create([
            'patient_id' => $patient->id,
            'status' => CaseStatus::Completed,
            'month' => now()->format('Y/m'),
            'is_valid' => true,
            'is_cancelled' => false,
            'origin_service' => 'UCI',
            'acquisition_type' => 'hospital',
            'special_population' => ['oncologico'],
        ]);

        $distributions = app(SepsisExecutiveSummaryService::class)->distributions();

        $this->assertSame(1, $distributions['origin_service']['UCI']);
        $this->assertSame(1, $distributions['acquisition_type']['Hospitalaria']);
        $this->assertSame(1, $distributions['special_population']['oncologico']);
    }

    public function test_new_pages_require_program_access(): void
    {
        $outsider = User::factory()->create();
        $leader = $this->leaderUser();

        $this->actingAs($outsider)->get(SiteResource::getUrl('index', panel: 'sepsis'))->assertForbidden();
        $this->actingAs($outsider)->get(ClinicalPathway::getUrl(panel: 'sepsis'))->assertForbidden();
        $this->actingAs($outsider)->get(MonthlyReport::getUrl(panel: 'sepsis'))->assertForbidden();

        $this->actingAs($leader)->get(SiteResource::getUrl('index', panel: 'sepsis'))->assertOk();
        $this->actingAs($leader)->get(ClinicalPathway::getUrl(panel: 'sepsis'))->assertOk();
        $this->actingAs($leader)->get(MonthlyReport::getUrl(panel: 'sepsis'))->assertOk();
    }

    public function test_clinical_pathway_lists_all_seventeen_steps(): void
    {
        $page = new ClinicalPathway;

        $this->assertCount(17, $page->steps());
        $this->assertCount(8, $page->timeline());
    }
}
