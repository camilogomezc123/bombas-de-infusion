<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\Pages;

use App\Filament\IcuLiberation\Resources\IcuStays\IcuStayResource;
use Filament\Resources\Pages\EditRecord;

class EditIcuStay extends EditRecord
{
    protected static string $resource = IcuStayResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [...$data, 'updated_by' => auth()->id()];
    }
}
