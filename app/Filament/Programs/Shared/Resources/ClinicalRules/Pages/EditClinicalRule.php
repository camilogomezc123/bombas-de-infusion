<?php

namespace App\Filament\Programs\Shared\Resources\ClinicalRules\Pages;

use App\Filament\Programs\Shared\Resources\ClinicalRules\ClinicalRuleResource;
use Filament\Resources\Pages\EditRecord;

class EditClinicalRule extends EditRecord
{
    protected static string $resource = ClinicalRuleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
