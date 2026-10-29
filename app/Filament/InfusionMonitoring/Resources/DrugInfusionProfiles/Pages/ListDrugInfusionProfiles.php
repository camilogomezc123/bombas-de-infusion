<?php

namespace App\Filament\InfusionMonitoring\Resources\DrugInfusionProfiles\Pages;

use App\Filament\InfusionMonitoring\Resources\DrugInfusionProfiles\DrugInfusionProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDrugInfusionProfiles extends ListRecords
{
    protected static string $resource = DrugInfusionProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo perfil')];
    }
}
