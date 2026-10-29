<?php

namespace Tests\Feature\Pics;

use App\Models\ClinicalProgram;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Support\Posuci\DailyTip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PortalDailyTipTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_todays_tip(): void
    {
        $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();
        $patient = Patient::query()->create(['identification' => uniqid('TIP-'), 'full_name' => 'Consejo diario']);
        $patient->update(['email' => 'tip-patient@test.com', 'must_change_password' => false]);
        PicsCase::query()->create(['clinical_program_id' => $program->id, 'patient_id' => $patient->id]);

        $this->actingAs($patient, 'patient')
            ->get('/portal')
            ->assertOk()
            ->assertSee('Consejo del día')
            ->assertSee(DailyTip::forDate(now()));
    }

    public function test_tip_changes_from_one_day_to_the_next_and_is_stable_within_the_same_day(): void
    {
        $today = DailyTip::forDate(Carbon::parse('2026-01-01'));
        $sameDayLater = DailyTip::forDate(Carbon::parse('2026-01-01 23:00'));
        $tomorrow = DailyTip::forDate(Carbon::parse('2026-01-02'));

        $this->assertSame($today, $sameDayLater);
        $this->assertNotSame($today, $tomorrow);
    }
}
