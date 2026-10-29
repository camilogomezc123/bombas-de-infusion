<?php

namespace App\Filament\Sepsis\Resources\ProgramResources\Pages;

use App\Filament\Sepsis\Resources\ProgramResources\ProgramResourceResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateProgramResource extends CreateRecord
{
    protected static string $resource = ProgramResourceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;

        return $data;
    }
}
