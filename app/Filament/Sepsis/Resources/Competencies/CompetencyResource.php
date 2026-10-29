<?php

namespace App\Filament\Sepsis\Resources\Competencies;

use App\Filament\Sepsis\Resources\Competencies\Pages\CreateCompetency;
use App\Filament\Sepsis\Resources\Competencies\Pages\EditCompetency;
use App\Filament\Sepsis\Resources\Competencies\Pages\ListCompetencies;
use App\Models\Competency;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompetencyResource extends Resource
{
    protected static ?string $model = Competency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'Programa';

    protected static ?string $navigationLabel = 'Competencias';

    protected static ?string $modelLabel = 'competencia';

    protected static ?string $pluralModelLabel = 'Competencias';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('clinical_program_id', ProgramAccess::program()?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Competencia')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Nombre')->required()->columnSpanFull(),
                    TextInput::make('role_key')->label('Rol / perfil')
                        ->helperText('Ej: Médico de urgencias, Enfermería, Farmacia, Intensivista.'),
                    TextInput::make('periodicity')->label('Periodicidad'),
                    TextInput::make('training_method')->label('Método de formación'),
                    TextInput::make('evaluation_method')->label('Método de evaluación'),
                    TextInput::make('minimum_score')->label('Puntaje mínimo')->numeric(),
                    Textarea::make('description')->label('Descripción')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Competencia')->wrap()->searchable(),
                TextColumn::make('role_key')->label('Rol / perfil')->placeholder('—'),
                TextColumn::make('periodicity')->label('Periodicidad')->placeholder('—'),
                TextColumn::make('staff_competencies_count')->label('Personal evaluado')->counts('staffCompetencies'),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompetencies::route('/'),
            'create' => CreateCompetency::route('/create'),
            'edit' => EditCompetency::route('/{record}/edit'),
        ];
    }
}
