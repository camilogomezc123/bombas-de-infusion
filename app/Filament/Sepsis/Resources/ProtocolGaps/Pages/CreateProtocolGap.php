<?php

namespace App\Filament\Sepsis\Resources\ProtocolGaps\Pages;

use App\Filament\Sepsis\Resources\ProtocolGaps\ProtocolGapResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateProtocolGap extends CreateRecord
{
    protected static string $resource = ProtocolGapResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;

        return $data;
    }
}
