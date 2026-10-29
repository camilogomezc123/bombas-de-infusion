<?php

namespace App\Filament\Sepsis\Resources\IndicatorDefinitions\Pages;

use App\Filament\Sepsis\Resources\IndicatorDefinitions\IndicatorDefinitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIndicatorDefinitions extends ListRecords
{
    protected static string $resource = IndicatorDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
