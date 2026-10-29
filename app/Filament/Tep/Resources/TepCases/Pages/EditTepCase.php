<?php

namespace App\Filament\Tep\Resources\TepCases\Pages;

use App\Filament\Tep\Resources\TepCases\TepCaseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTepCase extends EditRecord
{
    protected static string $resource = TepCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
