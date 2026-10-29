<?php

namespace App\Filament\InfusionMonitoring\Resources\DrugInfusionProfiles\Pages;

use App\Filament\InfusionMonitoring\Resources\DrugInfusionProfiles\DrugInfusionProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDrugInfusionProfile extends CreateRecord
{
    protected static string $resource = DrugInfusionProfileResource::class;
}
