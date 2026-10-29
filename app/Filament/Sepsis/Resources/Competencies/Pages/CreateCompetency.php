<?php

namespace App\Filament\Sepsis\Resources\Competencies\Pages;

use App\Filament\Sepsis\Resources\Competencies\CompetencyResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateCompetency extends CreateRecord
{
    protected static string $resource = CompetencyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;

        return $data;
    }
}
