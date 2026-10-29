<?php

namespace Tests\Feature\Sepsis;

use App\Models\Competency;
use App\Models\EvidenceDocument;
use App\Models\IndicatorDefinition;
use App\Models\ProtocolGap;
use App\Models\RaciAssignment;
use App\Models\SepsisPostsepsisFollowup;
use App\Models\StaffCompetency;
use Database\Seeders\GovernanceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_preloads_reference_content_without_patient_data(): void
    {
        $this->seed();

        $this->assertSame(10, ProtocolGap::query()->count());
        $this->assertGreaterThanOrEqual(6, IndicatorDefinition::query()->where('is_core_indicator', true)->count());
        $this->assertSame(14, EvidenceDocument::query()->where('document_reference', 'like', 'Pendiente de cargar%')->count());
        $this->assertGreaterThan(0, RaciAssignment::query()->count());
        $this->assertGreaterThan(0, Competency::query()->count());
    }

    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed();
        $firstCount = ProtocolGap::query()->count();

        $this->seed();
        $secondCount = ProtocolGap::query()->count();

        $this->assertSame($firstCount, $secondCount);
    }

    public function test_governance_demo_seeder_creates_postsepsis_followups_and_competency(): void
    {
        $this->seed();
        $this->seed(GovernanceDemoSeeder::class);

        $this->assertSame(5, SepsisPostsepsisFollowup::query()->count());
        $this->assertGreaterThan(0, StaffCompetency::query()->count());
    }
}
