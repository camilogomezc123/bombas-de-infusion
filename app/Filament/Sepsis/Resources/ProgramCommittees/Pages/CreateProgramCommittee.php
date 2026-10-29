<?php

namespace App\Filament\Sepsis\Resources\ProgramCommittees\Pages;

use App\Filament\Sepsis\Resources\ProgramCommittees\ProgramCommitteeResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateProgramCommittee extends CreateRecord
{
    protected static string $resource = ProgramCommitteeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;

        return $data;
    }
}
