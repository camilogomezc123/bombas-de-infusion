<?php

namespace App\Filament\Sepsis\Resources\ProgramResources;

use App\Filament\Sepsis\Resources\ProgramResources\Pages\CreateProgramResource;
use App\Filament\Sepsis\Resources\ProgramResources\Pages\EditProgramResource;
use App\Filament\Sepsis\Resources\ProgramResources\Pages\ListProgramResources;
use App\Models\ProgramResource;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramResourceResource extends Resource
{
    protected static ?string $model = ProgramResource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Programa';

    protected static ?string $navigationLabel = 'Recursos y acceso';

    protected static ?string $modelLabel = 'recurso del programa';

    protected static ?string $pluralModelLabel = 'Recursos y acceso';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('clinical_program_id', ProgramAccess::program()?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Verificación del recurso')
                ->columns(2)
                ->schema([
                    TextInput::make('resource_name')->label('Recurso')->required()->maxLength(150),
                    TextInput::make('service')->label('Servicio')->required()->maxLength(150)
                        ->helperText('Ej: Urgencias, Hospitalización, UCI Adultos, Unidad de Trasplante de Médula Ósea.'),
                    Select::make('availability')
                        ->label('Disponibilidad')
                        ->options([
                            'available' => 'Disponible',
                            'limited' => 'Disponible con limitaciones',
                            'unavailable' => 'No disponible',
                        ])
                        ->default('available')
                        ->required(),
                    Select::make('responsible_user_id')
                        ->label('Responsable')
                        ->relationship('responsible', 'name', fn (Builder $query) => $query->where('is_active', true))
                        ->searchable()
                        ->preload(),
                    DatePicker::make('verified_on')->label('Fecha de verificación'),
                    Textarea::make('finding')->label('Hallazgo')->columnSpanFull(),
                    Textarea::make('contingency')->label('Plan de contingencia')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('resource_name')->label('Recurso')->searchable()->sortable(),
                TextColumn::make('service')->label('Servicio')->searchable(),
                TextColumn::make('availability')
                    ->label('Disponibilidad')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'limited' => 'Con limitaciones',
                        'unavailable' => 'No disponible',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'limited' => 'warning',
                        'unavailable' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('responsible.name')->label('Responsable')->placeholder('Sin asignar'),
                TextColumn::make('verified_on')->label('Verificado')->date('d/m/Y')->placeholder('Sin verificar'),
            ])
            ->filters([
                SelectFilter::make('availability')->label('Disponibilidad')->options([
                    'available' => 'Disponible',
                    'limited' => 'Con limitaciones',
                    'unavailable' => 'No disponible',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('service');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProgramResources::route('/'),
            'create' => CreateProgramResource::route('/create'),
            'edit' => EditProgramResource::route('/{record}/edit'),
        ];
    }
}
