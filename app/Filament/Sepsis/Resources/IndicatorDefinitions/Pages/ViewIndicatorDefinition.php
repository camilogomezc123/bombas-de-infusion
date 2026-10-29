<?php

namespace App\Filament\Sepsis\Resources\IndicatorDefinitions\Pages;

use App\Filament\Sepsis\Resources\IndicatorDefinitions\IndicatorDefinitionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIndicatorDefinition extends ViewRecord
{
    protected static string $resource = IndicatorDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
