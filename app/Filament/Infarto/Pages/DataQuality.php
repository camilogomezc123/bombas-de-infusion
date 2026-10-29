<?php

namespace App\Filament\Infarto\Pages;

use App\Services\AcsDataQualityService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class DataQuality extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $navigationLabel = 'Calidad del dato';

    protected static ?string $title = 'Calidad del registro clínico';

    protected static ?int $navigationSort = 85;

    protected static ?string $slug = 'calidad-del-dato';

    protected string $view = 'filament.infarto.data-quality';

    #[Computed]
    public function rows()
    {
        return app(AcsDataQualityService::class)->report();
    }
}
