<?php

namespace App\Filament\Sepsis\Resources\IndicatorDefinitions\Pages;

use App\Filament\Sepsis\Resources\IndicatorDefinitions\IndicatorDefinitionResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateIndicatorDefinition extends CreateRecord
{
    protected static string $resource = IndicatorDefinitionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;

        return $data;
    }
}
