<?php

namespace App\Filament\Pics\Resources\PicsCases\Pages;

use App\Filament\Pics\Resources\PicsCases\PicsCaseResource;
use App\Filament\Pics\Support\PicsWorkflowActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPicsCase extends ViewRecord
{
    protected static string $resource = PicsCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...PicsWorkflowActions::for($this->getRecord()),
            EditAction::make(),
        ];
    }
}
