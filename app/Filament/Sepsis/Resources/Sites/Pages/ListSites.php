<?php

namespace App\Filament\Sepsis\Resources\Sites\Pages;

use App\Filament\Sepsis\Resources\Sites\SiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSites extends ListRecords
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
