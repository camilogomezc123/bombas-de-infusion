<?php

namespace App\Filament\Sepsis\Resources\ProgramResources\Pages;

use App\Filament\Sepsis\Resources\ProgramResources\ProgramResourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProgramResources extends ListRecords
{
    protected static string $resource = ProgramResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
