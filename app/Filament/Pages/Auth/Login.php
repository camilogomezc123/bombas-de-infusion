<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Usuario o correo')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $login = trim($data['username']);
        $field = str_contains($login, '@') ? 'email' : 'username';

        return [
            $field => $field === 'email' ? mb_strtolower($login) : mb_strtoupper($login),
            'password' => $data['password'],
            'is_active' => true,
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => 'El usuario, correo o contraseña no son correctos.',
        ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Bienvenido a ÁGORA';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Analítica y Gestión Operacional para Resultados Asistenciales';
    }
}
