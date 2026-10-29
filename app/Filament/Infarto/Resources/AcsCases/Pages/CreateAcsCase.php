<?php

namespace App\Filament\Infarto\Resources\AcsCases\Pages;

use App\Filament\Infarto\Resources\AcsCases\AcsCaseResource;
use App\Models\AcsCase;
use App\Support\ProgramAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateAcsCase extends CreateRecord
{
    protected static string $resource = AcsCaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $sequence = ((int) AcsCase::query()->max('case_sequence')) + 1;
        $data['clinical_program_id'] = ProgramAccess::program('INFARTO')?->id;
        $data['case_sequence'] = $sequence;
        $data['case_number'] = 'IAM-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
