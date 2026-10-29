<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\Pages;

use App\Filament\Sepsis\Resources\SepsisCases\SepsisCaseResource;
use App\Filament\Sepsis\Support\SepsisWorkflowActions;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSepsisCase extends EditRecord
{
    protected static string $resource = SepsisCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...SepsisWorkflowActions::for($this->getRecord()),
            ViewAction::make(),
        ];
    }
}
