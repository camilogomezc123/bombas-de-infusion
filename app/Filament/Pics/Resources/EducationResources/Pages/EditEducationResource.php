<?php

namespace App\Filament\Pics\Resources\EducationResources\Pages;

use App\Filament\Pics\Resources\EducationResources\EducationResourceResource;
use Filament\Resources\Pages\EditRecord;

class EditEducationResource extends EditRecord
{
    protected static string $resource = EducationResourceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth('web')->id();

        return $data;
    }
}
