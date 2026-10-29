<?php

namespace App\Filament\Sepsis\Resources\ClinicalPrograms;

use App\Filament\Sepsis\Resources\ClinicalPrograms\Pages\EditClinicalProgram;
use App\Filament\Sepsis\Resources\ClinicalPrograms\Pages\ListClinicalPrograms;
use App\Models\ClinicalProgram;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClinicalProgramResource extends Resource
{
    protected static ?string $model = ClinicalProgram::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Programa';

    protected static ?string $navigationLabel = 'Información general';

    protected static ?string $modelLabel = 'programa clínico';

    protected static ?string $pluralModelLabel = 'Información del programa';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('code', ProgramAccess::currentCode());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identidad del programa')->columns(2)->schema([
                TextInput::make('name')->label('Nombre')->required()->columnSpanFull(),
                TextInput::make('short_name')->label('Nombre corto')->required(),
                Select::make('status')->label('Estado')->options([
                    'implementation' => 'En implementación',
                    'active' => 'Activo',
                    'suspended' => 'Suspendido',
                    'archived' => 'Archivado',
                ])->required(),
                Textarea::make('description')->label('Descripción')->columnSpanFull(),
                Textarea::make('purpose')->label('Propósito')->columnSpanFull(),
                Textarea::make('mission')->label('Misión')->columnSpanFull(),
                Textarea::make('vision')->label('Visión')->columnSpanFull(),
            ]),
            Section::make('Alcance y población')->schema([
                Textarea::make('target_population')->label('Población objetivo'),
                Textarea::make('scope')->label('Alcance'),
                Textarea::make('inclusion_criteria')->label('Criterios de inclusión'),
                Textarea::make('exclusion_criteria')->label('Criterios de exclusión'),
            ]),
            Section::make('Liderazgo y vigencia')->columns(2)->schema([
                TextInput::make('executive_sponsor')->label('Patrocinador ejecutivo'),
                TextInput::make('medical_leader')->label('Liderazgo médico'),
                TextInput::make('nursing_leader')->label('Liderazgo de enfermería'),
                TextInput::make('quality_leader')->label('Liderazgo de calidad'),
                DatePicker::make('started_at')->label('Fecha de inicio'),
                TextInput::make('version')->label('Versión'),
                DatePicker::make('next_review_at')->label('Próxima revisión'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('short_name')->label('Programa'),
            TextColumn::make('status')->label('Estado')->badge()->icon('heroicon-m-information-circle'),
            TextColumn::make('version')->label('Versión'),
            TextColumn::make('next_review_at')->label('Próxima revisión')->date('d/m/Y'),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClinicalPrograms::route('/'),
            'edit' => EditClinicalProgram::route('/{record}/edit'),
        ];
    }
}
