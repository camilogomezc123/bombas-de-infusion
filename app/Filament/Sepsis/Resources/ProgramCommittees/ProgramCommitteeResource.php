<?php

namespace App\Filament\Sepsis\Resources\ProgramCommittees;

use App\Filament\Sepsis\Resources\ProgramCommittees\Pages\CreateProgramCommittee;
use App\Filament\Sepsis\Resources\ProgramCommittees\Pages\EditProgramCommittee;
use App\Filament\Sepsis\Resources\ProgramCommittees\Pages\ListProgramCommittees;
use App\Filament\Sepsis\Resources\ProgramCommittees\RelationManagers\MeetingsRelationManager;
use App\Filament\Sepsis\Resources\ProgramCommittees\RelationManagers\MembersRelationManager;
use App\Models\ProgramCommittee;
use App\Support\ProgramAccess;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramCommitteeResource extends Resource
{
    protected static ?string $model = ProgramCommittee::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Comité';

    protected static string|\UnitEnum|null $navigationGroup = 'Programa';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('clinical_program_id', ProgramAccess::program()?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')->required(),
            Textarea::make('purpose')->label('Propósito'),
            Select::make('status')->label('Estado')->options(['active' => 'Activo', 'inactive' => 'Inactivo'])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Comité'),
            TextColumn::make('status')->label('Estado')->badge()->icon('heroicon-m-information-circle'),
            TextColumn::make('meetings_count')->label('Reuniones')->counts('meetings'),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProgramCommittees::route('/'), 'create' => CreateProgramCommittee::route('/create'), 'edit' => EditProgramCommittee::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [MembersRelationManager::class, MeetingsRelationManager::class];
    }
}
