<?php

namespace App\Filament\InfusionMonitoring\Resources\InfusionClinicalAlerts\Pages;

use App\Filament\InfusionMonitoring\Resources\InfusionClinicalAlerts\InfusionClinicalAlertResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInfusionClinicalAlerts extends ListRecords
{
    protected static string $resource = InfusionClinicalAlertResource::class;

    public function getTabs(): array
    {
        return [
            'open' => Tab::make('Abiertas')->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'open')),
            'acknowledged' => Tab::make('Reconocidas')->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'acknowledged')),
            'resolved' => Tab::make('Resueltas')->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'resolved')),
            'all' => Tab::make('Todas'),
        ];
    }
}
