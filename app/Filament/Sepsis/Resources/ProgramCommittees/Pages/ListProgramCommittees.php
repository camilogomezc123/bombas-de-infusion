<?php

namespace App\Filament\Sepsis\Resources\ProgramCommittees\Pages;

use App\Filament\Sepsis\Resources\ProgramCommittees\ProgramCommitteeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProgramCommittees extends ListRecords
{
    protected static string $resource = ProgramCommitteeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
