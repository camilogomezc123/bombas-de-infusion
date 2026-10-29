<?php

namespace App\Filament\Sepsis\Resources\ProgramDocuments\Pages;

use App\Filament\Sepsis\Resources\ProgramDocuments\ProgramDocumentResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProgramDocument extends EditRecord
{
    protected static string $resource = ProgramDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }
}
