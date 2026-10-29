<?php

namespace App\Filament\Pics\Resources\SupportRequests\Pages;

use App\Filament\Pics\Resources\SupportRequests\SupportRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportRequests extends ListRecords
{
    protected static string $resource = SupportRequestResource::class;
}
