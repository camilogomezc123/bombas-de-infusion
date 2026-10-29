<?php

namespace App\Filament\Sepsis\Resources\ClinicalPrograms\Pages;

use App\Filament\Sepsis\Resources\ClinicalPrograms\ClinicalProgramResource;
use Filament\Resources\Pages\EditRecord;

class EditClinicalProgram extends EditRecord
{
    protected static string $resource = ClinicalProgramResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
