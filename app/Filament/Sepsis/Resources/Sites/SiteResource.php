<?php

namespace App\Filament\Sepsis\Resources\Sites;

use App\Filament\Sepsis\Resources\Sites\Pages\CreateSite;
use App\Filament\Sepsis\Resources\Sites\Pages\EditSite;
use App\Filament\Sepsis\Resources\Sites\Pages\ListSites;
use App\Models\Site;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|\UnitEnum|null $navigationGroup = 'Programa';

    protected static ?string $navigationLabel = 'Sedes';

    protected static ?string $modelLabel = 'sede';

    protected static ?string $pluralModelLabel = 'Sedes';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nombre')->required(),
            TextInput::make('code')->label('Código')->required()->maxLength(20),
            TextInput::make('address')->label('Dirección')->columnSpanFull(),
            Toggle::make('is_active')->label('Activa')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Sede')->searchable(),
                TextColumn::make('code')->label('Código'),
                TextColumn::make('address')->label('Dirección')->placeholder('—')->toggleable(),
                IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSites::route('/'),
            'create' => CreateSite::route('/create'),
            'edit' => EditSite::route('/{record}/edit'),
        ];
    }
}
