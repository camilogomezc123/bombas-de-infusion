<?php

namespace App\Filament\Sepsis\Resources\ProtocolGaps\Pages;

use App\Filament\Sepsis\Resources\ProtocolGaps\ProtocolGapResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProtocolGaps extends ListRecords
{
    protected static string $resource = ProtocolGapResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
