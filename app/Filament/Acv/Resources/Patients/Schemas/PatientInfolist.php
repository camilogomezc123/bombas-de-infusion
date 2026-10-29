<?php

namespace App\Filament\Acv\Resources\Patients\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Paciente')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('identification')->label('Identificación'),
                        TextEntry::make('full_name')->label('Nombres y apellidos'),
                        TextEntry::make('sex')->label('Sexo')->placeholder('Sin dato'),
                        TextEntry::make('age')->label('Edad')->suffix(' años')->placeholder('Sin dato'),
                        TextEntry::make('cases_count')->label('Número de casos')->state(fn ($record): int => $record->cases()->count()),
                    ]),
            ]);
    }
}
