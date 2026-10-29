<?php

namespace App\Filament\Sepsis\Resources\ClinicalPrograms\Pages;

use App\Filament\Sepsis\Resources\ClinicalPrograms\ClinicalProgramResource;
use Filament\Resources\Pages\ListRecords;

class ListClinicalPrograms extends ListRecords
{
    protected static string $resource = ClinicalProgramResource::class;
}
