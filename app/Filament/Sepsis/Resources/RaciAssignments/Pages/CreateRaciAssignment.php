<?php

namespace App\Filament\Sepsis\Resources\RaciAssignments\Pages;

use App\Filament\Sepsis\Resources\RaciAssignments\RaciAssignmentResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateRaciAssignment extends CreateRecord
{
    protected static string $resource = RaciAssignmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;

        return $data;
    }
}
