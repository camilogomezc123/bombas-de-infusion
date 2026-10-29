<?php

namespace App\Filament\Acv\Resources\AcvCases;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Filament\Acv\Resources\AcvCases\Pages\CreateAcvCase;
use App\Filament\Acv\Resources\AcvCases\Pages\EditAcvCase;
use App\Filament\Acv\Resources\AcvCases\Pages\ListAcvCases;
use App\Filament\Acv\Resources\AcvCases\Pages\ViewAcvCase;
use App\Filament\Acv\Resources\AcvCases\RelationManagers\AuditsRelationManager;
use App\Filament\Acv\Resources\AcvCases\RelationManagers\CommentsRelationManager;
use App\Filament\Acv\Resources\AcvCases\Schemas\AcvCaseForm;
use App\Filament\Acv\Resources\AcvCases\Schemas\AcvCaseInfolist;
use App\Filament\Acv\Resources\AcvCases\Tables\AcvCasesTable;
use App\Models\AcvCase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcvCaseResource extends Resource
{
    protected static ?string $model = AcvCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $modelLabel = 'caso ACV';

    protected static ?string $pluralModelLabel = 'Casos ACV';

    protected static ?string $navigationLabel = 'Casos ACV';

    protected static ?string $recordTitleAttribute = 'case_number';

    // Se accede desde los listados Activos/Egresados/Analizados, no desde el menú superior.
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if (! $user || $record->is_cancelled) {
            return false;
        }

        // Líder/administrador pueden editar siempre (correcciones, reapertura).
        if ($user->canManageAllCases()) {
            return true;
        }

        // El auditor asignado solo edita mientras el caso está en su poder
        // (Asignado / En revisión). Tras "Finalizar análisis" queda bloqueado.
        return $record->assigned_auditor_id === $user->id
            && $record->status->isAuditorEditable();
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (! $record->isPendingAnalysis() || $user->canManageAllCases()) {
            return true;
        }

        return $user->role === UserRole::Auditor
            && $record->assigned_auditor_id === $user->id;
    }

    public static function canCreate(): bool
    {
        // Solo el líder y el administrador crean casos; el auditor solo analiza los asignados.
        return auth()->user()?->canManageAllCases() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->canManageAllCases()) {
            return $query;
        }

        $pendingStatuses = CaseStatus::pendingAnalysis();

        return $query->where(function (Builder $query) use ($pendingStatuses, $user): void {
            $query
                ->whereNull('discharged_at')
                ->orWhereNotIn('status', $pendingStatuses);

            if ($user->role === UserRole::Auditor) {
                $query->orWhere('assigned_auditor_id', $user->id);
            }
        });
    }

    public static function form(Schema $schema): Schema
    {
        return AcvCaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AcvCaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcvCasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcvCases::route('/'),
            'create' => CreateAcvCase::route('/create'),
            'view' => ViewAcvCase::route('/{record}'),
            'edit' => EditAcvCase::route('/{record}/edit'),
        ];
    }
}
