<?php

namespace App\Filament\Pics\Resources\RecoveryGoals\Pages;

use App\Filament\Pics\Resources\RecoveryGoals\RecoveryGoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecoveryGoals extends ListRecords
{
    protected static string $resource = RecoveryGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva meta'),
        ];
    }
}
