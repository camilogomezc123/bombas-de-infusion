<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\Pages;

use App\Enums\CaseStatus;
use App\Enums\ProgramRole;
use App\Filament\Sepsis\Resources\SepsisCases\SepsisCaseResource;
use App\Support\ProgramAccess;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSepsisCases extends ListRecords
{
    protected static string $resource = SepsisCaseResource::class;

    public function getTitle(): string
    {
        return 'Base de datos de Sepsis';
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

        if ($user && ProgramAccess::hasRole($user, 'sepsis', ProgramRole::Auditor)) {
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
            'pendientes' => Tab::make('Pendientes de análisis')
                ->modifyQueryUsing(fn (Builder $q) => $q
                    ->whereNotNull('discharged_at')
                    ->whereIn('status', CaseStatus::pendingAnalysis())),
            'analizados' => Tab::make('Analizados')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', CaseStatus::Completed)),
        ];
    }
}
