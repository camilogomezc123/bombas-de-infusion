<?php

namespace App\Filament\Programs\Shared\Resources\ClinicalRules\Pages;

use App\Filament\Programs\Shared\Resources\ClinicalRules\ClinicalRuleResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateClinicalRule extends CreateRecord
{
    protected static string $resource = ClinicalRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
