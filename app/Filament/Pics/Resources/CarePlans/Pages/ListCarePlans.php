<?php

namespace App\Filament\Pics\Resources\CarePlans\Pages;

use App\Filament\Pics\Resources\CarePlans\CarePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCarePlans extends ListRecords
{
    protected static string $resource = CarePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo plan'),
        ];
    }
}
