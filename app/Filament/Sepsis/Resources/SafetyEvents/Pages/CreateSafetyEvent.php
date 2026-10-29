<?php

namespace App\Filament\Sepsis\Resources\SafetyEvents\Pages;

use App\Filament\Sepsis\Resources\SafetyEvents\SafetyEventResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateSafetyEvent extends CreateRecord
{
    protected static string $resource = SafetyEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;

        return $data;
    }
}
