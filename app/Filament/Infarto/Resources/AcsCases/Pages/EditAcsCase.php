<?php

namespace App\Filament\Infarto\Resources\AcsCases\Pages;

use App\Filament\Infarto\Resources\AcsCases\AcsCaseResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAcsCase extends EditRecord
{
    protected static string $resource = AcsCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
