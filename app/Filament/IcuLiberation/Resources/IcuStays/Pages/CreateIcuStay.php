<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\Pages;

use App\Filament\IcuLiberation\Resources\IcuStays\IcuStayResource;
use App\Models\IcuStay;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateIcuStay extends CreateRecord
{
    protected static string $resource = IcuStayResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $sequence = ((int) IcuStay::query()->max('case_sequence')) + 1;

        return [
            ...$data,
            'clinical_program_id' => ProgramAccess::program('ICULIB')?->id,
            'case_sequence' => $sequence,
            'case_number' => 'UCI-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];
    }
}
