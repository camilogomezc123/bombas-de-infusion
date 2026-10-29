<?php

namespace App\Filament\Tep\Resources\TepCases\Pages;

use App\Filament\Tep\Resources\TepCases\TepCaseResource;
use App\Models\TepCase;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateTepCase extends CreateRecord
{
    protected static string $resource = TepCaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $sequence = ((int) TepCase::query()->max('case_sequence')) + 1;

        return [...$data, 'clinical_program_id' => ProgramAccess::program('TEP')?->id, 'case_sequence' => $sequence, 'case_number' => 'TEP-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT), 'created_by' => auth()->id(), 'updated_by' => auth()->id()];
    }
}
