<?php

namespace App\Filament\Sepsis\Resources\ProgramDocuments\Pages;

use App\Filament\Sepsis\Resources\ProgramDocuments\ProgramDocumentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProgramDocument extends ViewRecord
{
    protected static string $resource = ProgramDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
