<?php

namespace App\Filament\Sepsis\Resources\ComplianceEvidence\Pages;

use App\Filament\Sepsis\Resources\ComplianceEvidence\ComplianceEvidenceResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditComplianceEvidence extends EditRecord
{
    protected static string $resource = ComplianceEvidenceResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }
}
