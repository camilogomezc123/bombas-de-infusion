<?php

namespace App\Filament\Acv\Resources\Patients;

use App\Enums\UserRole;
use App\Filament\Acv\Resources\Patients\Pages\CreatePatient;
use App\Filament\Acv\Resources\Patients\Pages\EditPatient;
use App\Filament\Acv\Resources\Patients\Pages\ListPatients;
use App\Filament\Acv\Resources\Patients\Pages\ViewPatient;
use App\Filament\Acv\Resources\Patients\Schemas\PatientForm;
use App\Filament\Acv\Resources\Patients\Schemas\PatientInfolist;
use App\Filament\Acv\Resources\Patients\Tables\PatientsTable;
use App\Models\Patient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $modelLabel = 'paciente';

    protected static ?string $pluralModelLabel = 'Pacientes';

    protected static ?string $recordTitleAttribute = 'full_name';

    // Los pacientes se gestionan desde los casos; "Base de datos" lista los casos.
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role !== UserRole::Viewer;
    }

    public static function getEloquentQuery(): Builder
    {
        // Para mostrar el Código ResQ (que vive en el caso) sin consultas N+1.
        return parent::getEloquentQuery()->with('cases:id,patient_id,resq_code');
    }

    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CasesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'view' => ViewPatient::route('/{record}'),
            'edit' => EditPatient::route('/{record}/edit'),
        ];
    }
}
