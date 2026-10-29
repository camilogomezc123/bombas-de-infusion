<?php

namespace App\Filament\Tep\Resources\TepCases\Pages;

use App\Filament\Tep\Resources\TepCases\TepCaseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTepCase extends ViewRecord
{
    protected static string $resource = TepCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
