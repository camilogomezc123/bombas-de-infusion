<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\Pages;

use App\Filament\IcuLiberation\Resources\IcuStays\IcuStayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListIcuStays extends ListRecords
{
    protected static string $resource = IcuStayResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva estancia')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas'),
            'active' => Tab::make('Activas')->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'active')->where('is_cancelled', false)),
            'ventilated' => Tab::make('Ventilados')->modifyQueryUsing(fn (Builder $q) => $q->where('mechanical_ventilation', true)->where('status', 'active')),
            'closed' => Tab::make('Cerradas')->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('completed_at')),
        ];
    }
}
