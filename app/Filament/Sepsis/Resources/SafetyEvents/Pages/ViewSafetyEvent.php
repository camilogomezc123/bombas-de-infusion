<?php

namespace App\Filament\Sepsis\Resources\SafetyEvents\Pages;

use App\Filament\Sepsis\Resources\SafetyEvents\SafetyEventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSafetyEvent extends ViewRecord
{
    protected static string $resource = SafetyEventResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
