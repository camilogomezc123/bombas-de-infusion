<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Usuario')
                    ->description(fn ($record): string => trim($record->username.($record->email ? '  ·  '.$record->email : '')))
                    ->searchable(['name', 'username', 'email'])
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Rol')
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->badge(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Último acceso')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options([
                        'administrator' => 'Administrador',
                        'leader' => 'Líder de programa',
                        'auditor' => 'Auditor',
                        'viewer' => 'Consulta',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->searchPlaceholder('Buscar por nombre, usuario o correo')
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->defaultSort('name');
    }
}
