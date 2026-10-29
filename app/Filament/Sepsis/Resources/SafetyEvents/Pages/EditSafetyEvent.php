<?php

namespace App\Filament\Sepsis\Resources\SafetyEvents\Pages;

use App\Filament\Sepsis\Resources\SafetyEvents\SafetyEventResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSafetyEvent extends EditRecord
{
    protected static string $resource = SafetyEventResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }
}
