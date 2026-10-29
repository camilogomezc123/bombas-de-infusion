<?php

namespace App\Filament\Sepsis\Resources\ProgramDocuments\Pages;

use App\Filament\Sepsis\Resources\ProgramDocuments\ProgramDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProgramDocuments extends ListRecords
{
    protected static string $resource = ProgramDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
