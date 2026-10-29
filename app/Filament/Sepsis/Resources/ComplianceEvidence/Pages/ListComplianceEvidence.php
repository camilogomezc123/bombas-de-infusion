<?php

namespace App\Filament\Sepsis\Resources\ComplianceEvidence\Pages;

use App\Filament\Sepsis\Resources\ComplianceEvidence\ComplianceEvidenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComplianceEvidence extends ListRecords
{
    protected static string $resource = ComplianceEvidenceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva evidencia')];
    }
}
