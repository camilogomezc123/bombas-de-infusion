<?php

namespace App\Filament\Acv\Resources\Patients\Pages;

use App\Filament\Acv\Resources\Patients\PatientResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
