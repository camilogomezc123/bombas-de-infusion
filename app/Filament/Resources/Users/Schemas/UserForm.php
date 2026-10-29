<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de acceso')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->label('Usuario')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (string $state): string => mb_strtoupper(trim($state)))
                            ->maxLength(50),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('role')
                            ->label('Rol')
                            ->options(collect(UserRole::cases())->mapWithKeys(
                                fn (UserRole $role): array => [$role->value => $role->label()]
                            ))
                            ->required(),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->rule(Password::min(6))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Déjala vacía al editar para conservar la contraseña actual.'),
                        Select::make('records_per_page')
                            ->label('Casos por página')
                            ->options([25 => 25, 50 => 50, 100 => 100, 200 => 200])
                            ->default(50)
                            ->selectablePlaceholder(false)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Usuario activo')
                            ->default(true),
                        Toggle::make('must_change_password')
                            ->label('Solicitar cambio de contraseña')
                            ->default(true),
                    ]),
            ]);
    }
}
