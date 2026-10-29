<?php

namespace App\Filament\Infarto\Pages;

use App\Models\AcsCase;
use App\Models\ClinicalProgram;
use App\Services\AcsIndicatorService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class ExecutiveSummary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Vista general';

    protected static ?string $title = 'Vista general de Infarto';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = '/';

    protected string $view = 'filament.infarto.executive-summary';

    #[Computed]
    public function summary(): array
    {
        $metrics = app(AcsIndicatorService::class)->dashboard(now()->format('Y'));
        $metrics['program_status'] = ClinicalProgram::query()->where('code', 'INFARTO')->value('status') ?? 'not_configured';
        $metrics['pending_audits'] = AcsCase::query()->whereHas('program', fn ($q) => $q->where('code', 'INFARTO'))
            ->where(fn ($q) => $q->where('acs_type', 'stemi')->orWhereNotNull('death_at'))->whereDoesntHave('audits')->count();
        $metrics['incomplete_cases'] = AcsCase::query()->whereHas('program', fn ($q) => $q->where('code', 'INFARTO'))
            ->where(fn ($q) => $q->whereNull('admission_at')->orWhereNull('ecg_interpreted_at')->orWhereNull('acs_type'))->count();

        return $metrics;
    }
}
