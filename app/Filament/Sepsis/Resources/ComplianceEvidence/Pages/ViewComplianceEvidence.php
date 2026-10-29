<?php

namespace App\Filament\Sepsis\Resources\ComplianceEvidence\Pages;

use App\Filament\Sepsis\Resources\ComplianceEvidence\ComplianceEvidenceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewComplianceEvidence extends ViewRecord
{
    protected static string $resource = ComplianceEvidenceResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
