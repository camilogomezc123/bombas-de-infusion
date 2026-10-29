<?php

namespace App\Filament\Tep\Pages;

use App\Models\TepCase;
use App\Services\TepIndicatorService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class ExecutiveSummary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Vista general';

    protected static ?string $title = 'Vista general de TEP';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = '/';

    protected string $view = 'filament.tep.executive-summary';

    #[Computed]
    public function summary(): array
    {
        $metrics = app(TepIndicatorService::class)->dashboard(now()->format('Y'));
        $metrics['in_process'] = TepCase::query()->whereNull('completed_at')->where('is_cancelled', false)->count();
        $metrics['pending_pert'] = TepCase::query()->whereIn('aha_category', ['C', 'D', 'E'])
            ->whereNull('pert_activated_at')->whereNull('pert_not_activated_reason')->where('is_cancelled', false)->count();

        return $metrics;
    }
}
