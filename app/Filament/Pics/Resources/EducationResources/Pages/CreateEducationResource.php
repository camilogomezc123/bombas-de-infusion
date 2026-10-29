<?php

namespace App\Filament\Pics\Resources\EducationResources\Pages;

use App\Filament\Pics\Resources\EducationResources\EducationResourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEducationResource extends CreateRecord
{
    protected static string $resource = EducationResourceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth('web')->id();
        $data['updated_by'] = auth('web')->id();

        return $data;
    }
}
