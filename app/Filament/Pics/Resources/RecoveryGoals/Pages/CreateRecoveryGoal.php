<?php

namespace App\Filament\Pics\Resources\RecoveryGoals\Pages;

use App\Filament\Pics\Resources\RecoveryGoals\RecoveryGoalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecoveryGoal extends CreateRecord
{
    protected static string $resource = RecoveryGoalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
