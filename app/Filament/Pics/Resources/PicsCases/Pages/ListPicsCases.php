<?php

namespace App\Filament\Pics\Resources\PicsCases\Pages;

use App\Enums\CaseStatus;
use App\Enums\ProgramRole;
use App\Filament\Pics\Resources\PicsCases\PicsCaseResource;
use App\Support\ProgramAccess;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPicsCases extends ListRecords
{
    protected static string $resource = PicsCaseResource::class;

    public function getTitle(): string
    {
        return 'Base de datos PICS';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo caso'),
        ];
    }

    /**
     * Los auditores solo ven sus casos asignados; líder/admin/consulta ven todos.
     */
    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery()->where('is_cancelled', false);
        $user = auth()->user();

        if ($user && ProgramAccess::hasRole($user, 'pics', ProgramRole::Auditor)) {
            $query->where('assigned_auditor_id', $user->id);
        }

        return $query;
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos'),
            'proceso' => Tab::make('En proceso')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', CaseStatus::inProcess())),
            'analizados' => Tab::make('Analizados')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', CaseStatus::Completed)),
        ];
    }
}
