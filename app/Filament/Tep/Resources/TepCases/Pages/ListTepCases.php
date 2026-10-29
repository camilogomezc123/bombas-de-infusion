<?php

namespace App\Filament\Tep\Resources\TepCases\Pages;

use App\Filament\Tep\Resources\TepCases\TepCaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTepCases extends ListRecords
{
    protected static string $resource = TepCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo caso')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos'),
            'process' => Tab::make('En proceso')->modifyQueryUsing(fn (Builder $q) => $q->whereNull('completed_at')->where('is_cancelled', false)),
            'pert' => Tab::make('PERT por resolver')->modifyQueryUsing(fn (Builder $q) => $q->whereIn('aha_category', ['C', 'D', 'E'])->whereNull('pert_activated_at')->whereNull('pert_not_activated_reason')->where('is_cancelled', false)),
            'closed' => Tab::make('Cerrados')->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('completed_at')),
        ];
    }
}
