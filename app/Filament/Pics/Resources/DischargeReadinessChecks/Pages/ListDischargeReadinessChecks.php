<?php

namespace App\Filament\Pics\Resources\DischargeReadinessChecks\Pages;

use App\Filament\Pics\Resources\DischargeReadinessChecks\DischargeReadinessCheckResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDischargeReadinessChecks extends ListRecords
{
    protected static string $resource = DischargeReadinessCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva preparación de alta'),
        ];
    }
}
