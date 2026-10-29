<?php

namespace App\Filament\Programs\Shared\Resources\ClinicalRules\Pages;

use App\Filament\Programs\Shared\Resources\ClinicalRules\ClinicalRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClinicalRules extends ListRecords
{
    protected static string $resource = ClinicalRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva versión')];
    }
}
