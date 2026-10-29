<?php

namespace App\Filament\Sepsis\Resources\ProgramMemberships\Pages;

use App\Filament\Sepsis\Resources\ProgramMemberships\ProgramMembershipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProgramMemberships extends ListRecords
{
    protected static string $resource = ProgramMembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Agregar integrante')];
    }
}
