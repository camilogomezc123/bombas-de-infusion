<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\Pages;

use App\Filament\Sepsis\Resources\SepsisCases\SepsisCaseResource;
use App\Filament\Sepsis\Support\SepsisWorkflowActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSepsisCase extends ViewRecord
{
    protected static string $resource = SepsisCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...SepsisWorkflowActions::for($this->getRecord()),
            EditAction::make(),
        ];
    }
}
