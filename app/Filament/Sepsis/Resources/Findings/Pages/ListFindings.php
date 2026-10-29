<?php

namespace App\Filament\Sepsis\Resources\Findings\Pages;

use App\Filament\Sepsis\Resources\Findings\FindingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFindings extends ListRecords
{
    protected static string $resource = FindingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
