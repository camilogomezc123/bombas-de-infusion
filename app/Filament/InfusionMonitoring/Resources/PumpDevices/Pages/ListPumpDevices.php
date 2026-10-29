<?php

namespace App\Filament\InfusionMonitoring\Resources\PumpDevices\Pages;

use App\Filament\InfusionMonitoring\Resources\PumpDevices\PumpDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPumpDevices extends ListRecords
{
    protected static string $resource = PumpDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva bomba')];
    }
}
