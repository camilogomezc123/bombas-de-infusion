<?php

namespace App\Filament\Acv\Resources\AcvCases\Pages;

use App\Filament\Acv\Resources\AcvCases\AcvCaseResource;
use App\Filament\Acv\Support\CaseWorkflowActions;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAcvCase extends EditRecord
{
    protected static string $resource = AcvCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...CaseWorkflowActions::for($this->getRecord()),
            ViewAction::make(),
        ];
    }
}
