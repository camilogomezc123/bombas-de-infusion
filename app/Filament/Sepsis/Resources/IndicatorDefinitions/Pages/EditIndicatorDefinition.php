<?php

namespace App\Filament\Sepsis\Resources\IndicatorDefinitions\Pages;

use App\Filament\Sepsis\Resources\IndicatorDefinitions\IndicatorDefinitionResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditIndicatorDefinition extends EditRecord
{
    protected static string $resource = IndicatorDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }
}
