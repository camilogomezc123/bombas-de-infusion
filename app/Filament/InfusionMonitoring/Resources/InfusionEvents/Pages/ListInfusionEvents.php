<?php

namespace App\Filament\InfusionMonitoring\Resources\InfusionEvents\Pages;

use App\Filament\InfusionMonitoring\Resources\InfusionEvents\InfusionEventResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInfusionEvents extends ListRecords
{
    protected static string $resource = InfusionEventResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos'),
            'unresolved' => Tab::make('Sin paciente asociado')
                ->modifyQueryUsing(fn (Builder $q) => $q->unresolved())
                ->badge(InfusionEventResource::getModel()::unresolved()->count()),
        ];
    }
}
