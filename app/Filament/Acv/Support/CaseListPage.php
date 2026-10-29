<?php

namespace App\Filament\Acv\Support;

use App\Enums\UserRole;
use App\Filament\Acv\Resources\AcvCases\AcvCaseResource;
use App\Filament\Acv\Resources\AcvCases\Tables\AcvCasesTable;
use App\Models\AcvCase;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base de los listados de casos (Activos / Egresados / Analizados).
 * Reutiliza las columnas del recurso y enlaza cada fila al detalle del caso.
 */
abstract class CaseListPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.case-list';

    abstract protected function scopeQuery(Builder $query): Builder;

    protected function getHeaderActions(): array
    {
        if (! AcvCaseResource::canCreate()) {
            return [];
        }

        return [
            Action::make('create')
                ->label('Nuevo caso')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->url(AcvCaseResource::getUrl('create')),
        ];
    }

    /**
     * Columnas de la tabla. Las páginas pueden sobreescribirlas (p. ej. Activos
     * muestra menos columnas porque el caso aún no está diligenciado).
     *
     * @return array<int, \Filament\Tables\Columns\Column>
     */
    protected function tableColumns(): array
    {
        return AcvCasesTable::columns();
    }

    /**
     * Filtros de la tabla. Las páginas pueden sobreescribirlos.
     *
     * @return array<int, \Filament\Tables\Filters\BaseFilter>
     */
    protected function tableFilters(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        // Tamaño de página por usuario; con una sola opción Filament oculta el selector.
        $perPage = auth()->user()?->recordsPerPage() ?? 50;

        return $table
            ->query($this->scopeQuery($this->baseQuery()))
            ->columns($this->tableColumns())
            ->filters($this->tableFilters())
            ->searchDebounce('400ms')
            ->paginationPageOptions([$perPage])
            ->defaultPaginationPageOption($perPage)
            ->recordActions([
                Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->url(fn (AcvCase $record): string => AcvCaseResource::getUrl('view', ['record' => $record])),
                Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-m-pencil-square')
                    ->visible(fn (AcvCase $record): bool => $this->canShowEditFor($record))
                    ->url(fn (AcvCase $record): string => AcvCaseResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('case_sequence', 'desc')
            ->striped()
            ->emptyStateHeading('Sin casos en esta vista');
    }

    /**
     * Si los auditores pueden ver el botón de edición en esta página.
     * Por defecto false; solo DischargedCases lo activa.
     */
    protected function auditorCanEdit(): bool
    {
        return false;
    }

    private function canShowEditFor(AcvCase $record): bool
    {
        $user = auth()->user();

        if (! $user || $record->is_cancelled) {
            return false;
        }

        if ($user->canManageAllCases()) {
            return AcvCaseResource::canEdit($record);
        }

        return $this->auditorCanEdit() && AcvCaseResource::canEdit($record);
    }

    /**
     * Consulta base: excluye anulados y, para los auditores, limita a sus casos asignados.
     * Líder, administrador y consulta ven todos los casos.
     */
    protected function baseQuery(): Builder
    {
        $query = AcvCase::query()
            ->with(['patient', 'assignedAuditor'])
            ->where('is_cancelled', false);

        $user = auth()->user();

        if ($user && $user->role === UserRole::Auditor) {
            $query->where('assigned_auditor_id', $user->id);
        }

        return $query;
    }
}
