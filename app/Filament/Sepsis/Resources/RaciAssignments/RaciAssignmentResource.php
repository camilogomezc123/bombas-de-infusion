<?php

namespace App\Filament\Sepsis\Resources\RaciAssignments;

use App\Filament\Sepsis\Resources\RaciAssignments\Pages\CreateRaciAssignment;
use App\Filament\Sepsis\Resources\RaciAssignments\Pages\EditRaciAssignment;
use App\Filament\Sepsis\Resources\RaciAssignments\Pages\ListRaciAssignments;
use App\Models\RaciAssignment;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RaciAssignmentResource extends Resource
{
    protected static ?string $model = RaciAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|\UnitEnum|null $navigationGroup = 'Programa';

    protected static ?string $navigationLabel = 'Matriz RACI';

    protected static ?string $modelLabel = 'asignación RACI';

    protected static ?string $pluralModelLabel = 'Matriz RACI';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('clinical_program_id', ProgramAccess::program()?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Actividad')
                ->columns(2)
                ->schema([
                    TextInput::make('activity')->label('Actividad')->required()->columnSpanFull(),
                    TextInput::make('responsible')->label('Responsable (R)')->required()
                        ->helperText('Quién ejecuta la actividad.'),
                    TextInput::make('approver')->label('Aprobador (A)')
                        ->helperText('Quién aprueba o rinde cuentas.'),
                    TextInput::make('consulted')->label('Consultado (C)'),
                    TextInput::make('informed')->label('Informado (I)'),
                    TextInput::make('service')->label('Servicio'),
                    DatePicker::make('valid_from')->label('Vigente desde'),
                    DatePicker::make('valid_until')->label('Vigente hasta')->afterOrEqual('valid_from'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('activity')->label('Actividad')->wrap()->searchable(),
                TextColumn::make('responsible')->label('R')->badge()->color('primary'),
                TextColumn::make('approver')->label('A')->badge()->color('warning')->placeholder('—'),
                TextColumn::make('consulted')->label('C')->badge()->color('info')->placeholder('—'),
                TextColumn::make('informed')->label('I')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('service')->label('Servicio')->placeholder('—')->toggleable(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('activity');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRaciAssignments::route('/'),
            'create' => CreateRaciAssignment::route('/create'),
            'edit' => EditRaciAssignment::route('/{record}/edit'),
        ];
    }
}
