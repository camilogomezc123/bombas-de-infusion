<?php

namespace App\Filament\Sepsis\Resources\SafetyEvents\Pages;

use App\Filament\Sepsis\Resources\SafetyEvents\SafetyEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSafetyEvents extends ListRecords
{
    protected static string $resource = SafetyEventResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
