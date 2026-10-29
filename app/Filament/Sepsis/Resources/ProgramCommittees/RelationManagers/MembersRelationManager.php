<?php

namespace App\Filament\Sepsis\Resources\ProgramCommittees\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Integrantes del comité';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')->label('Usuario de ÁGORA')->relationship('user', 'name')->searchable()->preload(),
            TextInput::make('display_name')->label('Nombre para mostrar')->required(),
            TextInput::make('email')->label('Correo electrónico')->email()
                ->helperText('Se usa para enviarle la convocatoria de las reuniones. Si el integrante tiene usuario de ÁGORA, se usa ese correo si este campo queda vacío.'),
            TextInput::make('discipline')->label('Disciplina'),
            TextInput::make('committee_role')->label('Rol en el comité'),
            Toggle::make('is_active')->label('Activo')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label('Integrante'),
            TextColumn::make('email')->label('Correo')->placeholder('Sin correo propio')->toggleable(),
            TextColumn::make('discipline')->label('Disciplina'),
            TextColumn::make('committee_role')->label('Rol'), IconColumn::make('is_active')->label('Activo')->boolean(),
        ])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
