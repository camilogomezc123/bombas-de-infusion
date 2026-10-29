<?php

namespace App\Filament\Sepsis\Resources\QualityStandards\Pages;

use App\Filament\Sepsis\Resources\QualityStandards\QualityStandardResource;
use Filament\Resources\Pages\ListRecords;

class ListQualityStandards extends ListRecords
{
    protected static string $resource = QualityStandardResource::class;

    public function getTitle(): string
    {
        return 'Elementos evaluables';
    }
}
