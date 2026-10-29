<?php

namespace App\Filament\Sepsis\Resources\ComplianceEvidence\Pages;

use App\Filament\Sepsis\Resources\ComplianceEvidence\ComplianceEvidenceResource;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateComplianceEvidence extends CreateRecord
{
    protected static string $resource = ComplianceEvidenceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinical_program_id'] = ProgramAccess::program()?->id;
        $data['uploaded_by'] = auth()->id();

        return $data;
    }
}
