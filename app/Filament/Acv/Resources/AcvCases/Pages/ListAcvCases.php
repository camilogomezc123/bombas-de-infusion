<?php

namespace App\Filament\Acv\Resources\AcvCases\Pages;

use App\Filament\Acv\Resources\AcvCases\AcvCaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcvCases extends ListRecords
{
    protected static string $resource = AcvCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
