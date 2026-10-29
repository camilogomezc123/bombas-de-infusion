<?php

namespace App\Filament\Acv\Resources\AcvCases\Pages;

use App\Filament\Acv\Resources\AcvCases\AcvCaseResource;
use App\Filament\Acv\Support\CaseWorkflowActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAcvCase extends ViewRecord
{
    protected static string $resource = AcvCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...CaseWorkflowActions::for($this->getRecord()),
            EditAction::make(),
        ];
    }
}
