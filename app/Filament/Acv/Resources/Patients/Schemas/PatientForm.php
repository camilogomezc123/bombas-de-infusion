<?php

namespace App\Filament\Acv\Resources\Patients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación del paciente')
                    ->columns(2)
                    ->schema([
                        TextInput::make('identification')
                            ->label('Identificación')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        TextInput::make('full_name')
                            ->label('Nombres y apellidos')
                            ->required()
                            ->maxLength(255),
                        Select::make('sex')
                            ->label('Sexo')
                            ->options([
                                'Hombre' => 'Hombre',
                                'Mujer' => 'Mujer',
                                'Otro' => 'Otro',
                            ]),
                        TextInput::make('age')
                            ->label('Edad')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(130),
                    ]),
            ]);
    }
}
