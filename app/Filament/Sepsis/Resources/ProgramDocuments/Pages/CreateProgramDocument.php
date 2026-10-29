<?php

namespace App\Filament\Sepsis\Resources\ProgramDocuments\Pages;

use App\Filament\Sepsis\Resources\ProgramDocuments\ProgramDocumentResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateProgramDocument extends CreateRecord
{
    protected static string $resource = ProgramDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;
        $data['uploaded_by'] = auth()->id();

        return $data;
    }
}
