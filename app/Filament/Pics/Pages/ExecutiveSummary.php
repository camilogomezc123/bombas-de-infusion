<?php

namespace App\Filament\Pics\Pages;

use App\Models\PicsCase;
use App\Services\PicsIndicatorService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class ExecutiveSummary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Vista general';

    protected static ?string $title = 'Vista general de PICS';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = '/';

    protected string $view = 'filament.pics.executive-summary';

    #[Computed]
    public function summary(): array
    {
        $metrics = app(PicsIndicatorService::class)->summary(now()->format('Y')) ?? [
            'total' => 0, 'followups_total' => 0, 'referrals_total' => 0,
            'followup_rate_pct' => null, 'screening_positive_pct' => null, 'referral_completion_pct' => null,
            'readmission_pct' => null, 'return_to_work_pct' => null, 'contact_achieved_pct' => null,
        ];
        $metrics['open_cases'] = PicsCase::query()
            ->where('is_cancelled', false)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        return $metrics;
    }
}
