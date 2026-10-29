<?php

namespace App\Filament\Infarto\Resources\AcsCases\Pages;

use App\Filament\Infarto\Resources\AcsCases\AcsCaseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAcsCase extends ViewRecord
{
    protected static string $resource = AcsCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
