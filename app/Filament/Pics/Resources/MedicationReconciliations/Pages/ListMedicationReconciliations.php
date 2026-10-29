<?php

namespace App\Filament\Pics\Resources\MedicationReconciliations\Pages;

use App\Filament\Pics\Resources\MedicationReconciliations\MedicationReconciliationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMedicationReconciliations extends ListRecords
{
    protected static string $resource = MedicationReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva conciliación'),
        ];
    }
}
