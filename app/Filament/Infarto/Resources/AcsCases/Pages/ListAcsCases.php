<?php

namespace App\Filament\Infarto\Resources\AcsCases\Pages;

use App\Filament\Infarto\Resources\AcsCases\AcsCaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAcsCases extends ListRecords
{
    protected static string $resource = AcsCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo caso')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos'),
            'process' => Tab::make('En proceso')->modifyQueryUsing(fn (Builder $q) => $q->whereNull('completed_at')->where('is_cancelled', false)),
            'audit' => Tab::make('Pendientes de auditoría')->modifyQueryUsing(fn (Builder $q) => $q->where(fn ($x) => $x->where('acs_type', 'stemi')->orWhereNotNull('death_at'))->whereDoesntHave('audits')),
            'closed' => Tab::make('Cerrados')->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('completed_at')),
        ];
    }
}
