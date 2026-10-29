<?php

namespace App\Filament\Acv\Resources\Patients\Pages;

use App\Filament\Acv\Resources\Patients\PatientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;
}
