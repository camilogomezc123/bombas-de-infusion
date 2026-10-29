<?php

namespace App\Filament\Pics\Resources\EducationResources\Pages;

use App\Filament\Pics\Resources\EducationResources\EducationResourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEducationResources extends ListRecords
{
    protected static string $resource = EducationResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo contenido'),
        ];
    }
}
