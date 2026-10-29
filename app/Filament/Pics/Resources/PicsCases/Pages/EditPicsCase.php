<?php

namespace App\Filament\Pics\Resources\PicsCases\Pages;

use App\Filament\Pics\Resources\PicsCases\PicsCaseResource;
use App\Filament\Pics\Support\PicsWorkflowActions;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPicsCase extends EditRecord
{
    protected static string $resource = PicsCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...PicsWorkflowActions::for($this->getRecord()),
            ViewAction::make(),
        ];
    }
}
