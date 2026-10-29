<?php

namespace App\Filament\Pics\Resources\RecoveryPassports\Pages;

use App\Filament\Pics\Resources\RecoveryPassports\RecoveryPassportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecoveryPassports extends ListRecords
{
    protected static string $resource = RecoveryPassportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo pasaporte'),
        ];
    }
}
