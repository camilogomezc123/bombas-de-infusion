<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\Pages;

use App\Filament\IcuLiberation\Resources\IcuStays\IcuStayResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIcuStay extends ViewRecord
{
    protected static string $resource = IcuStayResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
