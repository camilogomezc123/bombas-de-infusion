<?php

namespace App\Filament\Sepsis\Resources\ProgramMemberships;

use App\Enums\ProgramRole;
use App\Filament\Sepsis\Resources\ProgramMemberships\Pages\CreateProgramMembership;
use App\Filament\Sepsis\Resources\ProgramMemberships\Pages\EditProgramMembership;
use App\Filament\Sepsis\Resources\ProgramMemberships\Pages\ListProgramMemberships;
use App\Filament\Sepsis\Resources\ProgramMemberships\RelationManagers\StaffCompetenciesRelationManager;
use App\Models\ProgramMember;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramMembershipResource extends Resource
{
    protected static ?string $model = ProgramMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Programa';

    protected static ?string $navigationLabel = 'Equipo y competencias';

    protected static ?string $modelLabel = 'integrante del equipo';

    protected static ?string $pluralModelLabel = 'Equipo y competencias';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('program', fn (Builder $query) => $query->where('code', ProgramAccess::currentCode()));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asignación al programa')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->label('Usuario')
                        ->relationship('user', 'name', fn (Builder $query) => $query->where('is_active', true))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->unique(
                            table: ProgramMember::class,
                            column: 'user_id',
                            ignoreRecord: true,
                            modifyRuleUsing: fn ($rule) => $rule->where(
                                'clinical_program_id',
                                ProgramAccess::program()?->id,
                            ),
                        ),
                    Select::make('role')
                        ->label('Responsabilidad')
                        ->options(collect(ProgramRole::cases())
                            ->mapWithKeys(fn (ProgramRole $role): array => [$role->value => $role->label()]))
                        ->required(),
                    DatePicker::make('starts_on')->label('Vigente desde'),
                    DatePicker::make('ends_on')->label('Vigente hasta')->afterOrEqual('starts_on'),
                    Toggle::make('is_active')->label('Asignación activa')->default(true),
                ]),

            Section::make('Competencias en la ruta clínica')
                ->columns(2)
                ->schema([
                    TextInput::make('discipline')->label('Disciplina')->maxLength(150)
                        ->helperText('Ej: Medicina interna, Enfermería, Cuidado crítico.'),
                    TextInput::make('service')->label('Servicio')->maxLength(150)
                        ->helperText('Ej: Urgencias, Hospitalización, UCI Adultos, UTMO.'),
                    TextInput::make('route_role')->label('Rol en la ruta')->maxLength(150)
                        ->helperText('Ej: Activa el código, valida tiempo cero, ajusta antimicrobianos.'),
                    Select::make('competency_result')
                        ->label('Resultado de evaluación')
                        ->options([
                            'approved' => 'Aprobado',
                            'partial' => 'Aprobado con plan de mejora',
                            'not_approved' => 'No aprobado',
                            'not_evaluated' => 'Sin evaluar',
                        ]),
                    Textarea::make('required_competencies')->label('Competencias requeridas')->columnSpanFull(),
                    Textarea::make('evaluated_competencies')->label('Competencias evaluadas')->columnSpanFull(),
                    DatePicker::make('competency_evaluated_on')->label('Fecha de evaluación'),
                    DatePicker::make('competency_valid_until')->label('Vigencia de la competencia'),
                    Toggle::make('retraining_required')->label('Requiere reentrenamiento'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Integrante')->searchable()->sortable(),
                TextColumn::make('role')
                    ->label('Responsabilidad')
                    ->formatStateUsing(fn (ProgramRole $state): string => $state->label())
                    ->badge(),
                TextColumn::make('service')->label('Servicio')->placeholder('Sin dato')->toggleable(),
                TextColumn::make('starts_on')->label('Desde')->date('d/m/Y')->placeholder('Sin límite'),
                TextColumn::make('ends_on')->label('Hasta')->date('d/m/Y')->placeholder('Sin límite'),
                TextColumn::make('competency_valid_until')->label('Competencia vigente hasta')
                    ->date('d/m/Y')->placeholder('Sin evaluar')->toggleable(),
                IconColumn::make('retraining_required')->label('Reentrenamiento')->boolean()->toggleable(),
                IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [StaffCompetenciesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProgramMemberships::route('/'),
            'create' => CreateProgramMembership::route('/create'),
            'edit' => EditProgramMembership::route('/{record}/edit'),
        ];
    }
}
