<?php

namespace App\Filament\IcuLiberation\Pages;

use App\Models\IcuStay;
use App\Services\IcuLiberationIndicatorService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class ExecutiveSummary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Vista general';

    protected static ?string $title = 'Vista general de ICU Liberation';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = '/';

    protected string $view = 'filament.icu-liberation.executive-summary';

    #[Computed]
    public function summary(): array
    {
        $metrics = app(IcuLiberationIndicatorService::class)->dashboard(now()->format('Y'));
        $metrics['active_stays'] = IcuStay::query()->where('status', 'active')->where('is_cancelled', false)->count();
        $metrics['restraints_active'] = IcuStay::query()
            ->whereHas('physicalRestraints', fn ($q) => $q->whereNull('removed_at'))
            ->where('status', 'active')->count();
        $metrics['pics_followups_overdue'] = app(IcuLiberationIndicatorService::class)->overduePicsFollowups();

        return $metrics;
    }
}
