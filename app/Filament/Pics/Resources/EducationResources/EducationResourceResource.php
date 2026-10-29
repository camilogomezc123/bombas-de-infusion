<?php

namespace App\Filament\Pics\Resources\EducationResources;

use App\Filament\Pics\Resources\EducationResources\Pages\CreateEducationResource;
use App\Filament\Pics\Resources\EducationResources\Pages\EditEducationResource;
use App\Filament\Pics\Resources\EducationResources\Pages\ListEducationResources;
use App\Models\EducationResource;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EducationResourceResource extends Resource
{
    protected static ?string $model = EducationResource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Educación personalizada';

    protected static ?string $modelLabel = 'contenido educativo';

    protected static ?string $pluralModelLabel = 'Educación personalizada';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Título')->required()->maxLength(150)->columnSpanFull(),
            Select::make('category')->label('Categoría')->options(EducationResource::CATEGORIES),
            Select::make('audience')->label('Dirigido a')->options(EducationResource::AUDIENCES)->default('ambos')->required(),
            Toggle::make('is_active')->label('Activo (disponible para asignar)')->default(true),
            Textarea::make('body')->label('Contenido')->rows(8)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Título')->searchable(),
                TextColumn::make('category')->label('Categoría')
                    ->formatStateUsing(fn (EducationResource $record): string => $record->categoryLabel()),
                TextColumn::make('audience')->label('Dirigido a')
                    ->formatStateUsing(fn (?string $state): string => EducationResource::AUDIENCES[$state] ?? '—'),
                IconColumn::make('is_active')->label('Activo')->boolean(),
                TextColumn::make('assignments_count')->label('Casos asignados')->counts('assignments'),
            ])
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEducationResources::route('/'),
            'create' => CreateEducationResource::route('/create'),
            'edit' => EditEducationResource::route('/{record}/edit'),
        ];
    }
}
