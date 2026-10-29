<?php

namespace App\Filament\Sepsis\Resources\ProgramMemberships\Pages;

use App\Filament\Sepsis\Resources\ProgramMemberships\ProgramMembershipResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateProgramMembership extends CreateRecord
{
    protected static string $resource = ProgramMembershipResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;
        $data['granted_by'] = auth()->id();

        return $data;
    }
}
