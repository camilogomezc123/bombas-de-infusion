<?php

namespace App\Filament\Sepsis\Resources\SepsisCases;

use App\Enums\ProgramRole;
use App\Filament\Sepsis\Resources\SepsisCases\Pages\CreateSepsisCase;
use App\Filament\Sepsis\Resources\SepsisCases\Pages\EditSepsisCase;
use App\Filament\Sepsis\Resources\SepsisCases\Pages\ListSepsisCases;
use App\Filament\Sepsis\Resources\SepsisCases\Pages\ViewSepsisCase;
use App\Filament\Sepsis\Resources\SepsisCases\RelationManagers\AntimicrobialAdministrationsRelationManager;
use App\Filament\Sepsis\Resources\SepsisCases\RelationManagers\BundleTasksRelationManager;
use App\Filament\Sepsis\Resources\SepsisCases\RelationManagers\CareTransitionsRelationManager;
use App\Filament\Sepsis\Resources\SepsisCases\RelationManagers\ClinicalAuditsRelationManager;
use App\Filament\Sepsis\Resources\SepsisCases\RelationManagers\CulturesRelationManager;
use App\Filament\Sepsis\Resources\SepsisCases\RelationManagers\EducationRecordsRelationManager;
use App\Filament\Sepsis\Resources\SepsisCases\RelationManagers\HemodynamicAssessmentsRelationManager;
use App\Filament\Sepsis\Resources\SepsisCases\RelationManagers\PostsepsisFollowupsRelationManager;
use App\Filament\Sepsis\Resources\SepsisCases\RelationManagers\ScreeningsRelationManager;
use App\Filament\Sepsis\Resources\SepsisCases\RelationManagers\SourceControlActionsRelationManager;
use App\Filament\Sepsis\Resources\SepsisCases\Schemas\SepsisCaseForm;
use App\Filament\Sepsis\Resources\SepsisCases\Schemas\SepsisCaseInfolist;
use App\Filament\Sepsis\Resources\SepsisCases\Tables\SepsisCasesTable;
use App\Models\SepsisCase;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SepsisCaseResource extends Resource
{
    protected static ?string $model = SepsisCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Casos';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'caso de Sepsis';

    protected static ?string $pluralModelLabel = 'Casos de Sepsis';

    protected static ?string $recordTitleAttribute = 'case_number';

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update', $record) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', SepsisCase::class) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->whereHas(
            'program',
            fn (Builder $query) => $query->whereRaw('upper(code) = ?', ['SEPSIS']),
        );
        $user = auth()->user();

        if ($user && ProgramAccess::hasRole($user, 'sepsis', ProgramRole::Auditor)) {
            $query->where('assigned_auditor_id', $user->id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return SepsisCaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SepsisCaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SepsisCasesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSepsisCases::route('/'),
            'create' => CreateSepsisCase::route('/create'),
            'view' => ViewSepsisCase::route('/{record}'),
            'edit' => EditSepsisCase::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ScreeningsRelationManager::class,
            BundleTasksRelationManager::class,
            HemodynamicAssessmentsRelationManager::class,
            CulturesRelationManager::class,
            AntimicrobialAdministrationsRelationManager::class,
            SourceControlActionsRelationManager::class,
            CareTransitionsRelationManager::class,
            EducationRecordsRelationManager::class,
            PostsepsisFollowupsRelationManager::class,
            ClinicalAuditsRelationManager::class,
        ];
    }
}
